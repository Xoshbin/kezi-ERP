
import { db } from '../db/pos-db';
import { getTranslation } from '../translations';

export function useReceipt() {
    const locale = document.documentElement.lang?.substring(0, 2) || 'en';
    const t = (key) => getTranslation(key, locale);

    const generateReceiptHtml = async (orderId) => {
        // 1. Fetch Data
        const order = await db.orders.get(orderId);
        if (!order) throw new Error(`Order ${orderId} not found`);

        const lines = await db.order_lines.where('order_id').equals(orderId).toArray();
        
        // Enhance lines with product names
        for (const line of lines) {
            const product = await db.products.get(line.product_id);
            line.product_name = product?.name || t('receipt.unknown_product');
        }

        // Get Customer
        let customer = null;
        if (order.customer_id) {
            customer = await db.customers.get(order.customer_id);
        }

        // Get Currency
        const currencySetting = await db.settings.get('company_currency');
        const currencyCode = currencySetting?.value?.code || 'USD';
        const decimalPlaces = currencySetting?.value?.decimal_places ?? 2;
        const decimalFactor = Math.pow(10, decimalPlaces);
        
        // Helper for currency formatting
        const formatMoney = (amount) => {
            if (amount === undefined || amount === null) return '0.00';
            const val = Number(amount) / decimalFactor;
            return new Intl.NumberFormat('en-US', { 
                style: 'currency', 
                currency: currencyCode,
                minimumFractionDigits: decimalPlaces,
                maximumFractionDigits: decimalPlaces
            }).format(val);
        };

        const formatDate = (isoString) => {
            if (!isoString) return '';
            const date = new Date(isoString);
            return date.toLocaleString();
        };

        // 2. Build HTML
        const styles = `
            body { font-family: 'Courier New', monospace; font-size: 12px; margin: 0; padding: 0; width: 300px; color: #000; }
            .header { text-align: center; margin-bottom: 10px; border-bottom: 1px dashed #000; padding-bottom: 5px; }
            .title { font-weight: bold; font-size: 16px; margin-bottom: 5px; }
            .meta { font-size: 10px; margin-bottom: 5px; }
            .table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
            .row { display: flex; justify-content: space-between; margin-bottom: 2px; }
            .item-name { font-weight: bold; }
            .item-details { padding-left: 10px; font-size: 10px; color: #444; }
            .divider { border-top: 1px dashed #000; margin: 5px 0; }
            .totals { margin-bottom: 10px; }
            .total-row { display: flex; justify-content: space-between; font-weight: bold; font-size: 14px; margin-top: 5px; }
            .footer { text-align: center; font-size: 10px; margin-top: 10px; border-top: 1px dashed #000; padding-top: 5px; }
            @media print {
                body { width: 100%; }
                @page { margin: 0; size: 80mm auto; }
            }
        `;

        const itemsHtml = lines.map(line => `
            <div style="margin-bottom: 5px;">
                <div class="row item-name">
                    <span>${line.product_name}</span>
                    <span>x ${line.quantity}</span>
                </div>
                <div class="row item-details">
                    <span>@ ${formatMoney(line.unit_price)}</span>
                    <span>${formatMoney(line.unit_price * line.quantity)}</span>
                </div>
                ${line.discount_amount > 0 ? `
                    <div class="row item-details" style="color: #444;">
                        <span>${t('receipt.line_discount')}</span>
                        <span>-${formatMoney(line.discount_amount)}</span>
                    </div>
                ` : ''}
            </div>
        `).join('');

        return `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <title>${t('receipt.header')} ${order.order_number}</title>
                <style>${styles}</style>
            </head>
            <body>
                <div class="header">
                    <div class="title">${t('receipt.title')}</div>
                    <div>${t('receipt.header')}</div>
                    <div class="meta">
                        <div>${t('receipt.date')}: ${formatDate(order.ordered_at)}</div>
                        <div>${t('receipt.order')}: ${order.order_number}</div>
                        <div>${t('receipt.session')}: #${order.pos_session_id}</div>
                        ${customer ? `<div>${t('receipt.customer')}: ${customer.name}</div>` : ''}
                    </div>
                </div>

                <div class="items">
                    ${itemsHtml}
                </div>

                <div class="divider"></div>

                <div class="totals">
                    <div class="row">
                        <span>${t('receipt.subtotal')}</span>
                        <span>${formatMoney(lines.reduce((sum, l) => sum + (l.unit_price * l.quantity), 0))}</span>
                    </div>
                    ${order.discount_amount > 0 ? `
                        <div class="row" style="font-weight: bold;">
                            <span>${t('receipt.discount')}</span>
                            <span>-${formatMoney(order.discount_amount)}</span>
                        </div>
                    ` : ''}
                    <div class="row">
                        <span>${t('receipt.tax')}</span>
                        <span>${formatMoney(order.total_tax)}</span>
                    </div>
                    <div class="total-row">
                        <span>${t('receipt.total')}</span>
                        <span>${formatMoney(order.total_amount)}</span>
                    </div>
                </div>

                <div class="divider"></div>

                <div class="payment">
                    <div class="row">
                        <span>${t('receipt.method')}</span>
                        <span style="text-transform: capitalize">${order.payment_method}</span>
                    </div>
                    <div class="row">
                        <span>${t('receipt.tendered')}</span>
                        <span>${formatMoney(order.amount_tendered)}</span>
                    </div>
                    <div class="row">
                        <span>${t('receipt.change')}</span>
                        <span>${formatMoney(order.change_given)}</span>
                    </div>
                </div>

                <div class="footer">
                    <div>${t('receipt.footer')}</div>
                    <div style="margin-top: 10px; font-size: 8px;">${order.uuid}</div>
                </div>
            </body>
            </html>
        `;
    };

    const printReceipt = async (orderId) => {
        try {
            const html = await generateReceiptHtml(orderId);
            
            // Create hidden iframe
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.style.position = 'fixed';
            iframe.style.right = '0';
            iframe.style.bottom = '0';
            document.body.appendChild(iframe);
            
            const doc = iframe.contentDocument || iframe.contentWindow.document;
            doc.open();
            doc.write(html);
            doc.close();
            
            // Wait for content to render/load
            iframe.onload = () => {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
                
                // Cleanup after a delay to allow print dialog to open
                setTimeout(() => {
                    document.body.removeChild(iframe);
                }, 2000);
            };
        } catch (error) {
            console.error('Failed to print receipt:', error);
            alert('Could not print receipt. Please try again.');
        }
    };

    /**
     * 6b — Return receipt generator.
     * Accepts a return object (as returned by the API / sync-service) with:
     *   - return_number, return_date, refund_method, return_reason, uuid
     *   - lines[]  each with product_name, quantity_returned, unit_price, refund_amount
     *   - originalOrder { order_number, customer } or original_order_number
     */
    const generateReturnReceiptHtml = async (returnData) => {
        const currencySetting = await db.settings.get('company_currency');
        const currencyCode = currencySetting?.value?.code || 'USD';
        const decimalPlaces = currencySetting?.value?.decimal_places ?? 2;
        const decimalFactor = Math.pow(10, decimalPlaces);

        const formatMoney = (amount) => {
            if (amount === undefined || amount === null) return '0.00';
            const val = Number(amount) / decimalFactor;
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currencyCode,
                minimumFractionDigits: decimalPlaces,
                maximumFractionDigits: decimalPlaces,
            }).format(val);
        };

        const formatDate = (isoString) => {
            if (!isoString) return '';
            return new Date(isoString).toLocaleString();
        };

        const returnStyles = `
            body { font-family: 'Courier New', monospace; font-size: 12px; margin: 0; padding: 0; width: 300px; color: #000; }
            .header { text-align: center; margin-bottom: 10px; border-bottom: 1px dashed #000; padding-bottom: 5px; }
            .return-title { font-weight: bold; font-size: 16px; letter-spacing: 2px; margin-bottom: 3px; border: 2px solid #000; display: inline-block; padding: 2px 8px; }
            .meta { font-size: 10px; margin-bottom: 5px; }
            .row { display: flex; justify-content: space-between; margin-bottom: 2px; }
            .item-name { font-weight: bold; }
            .item-details { padding-left: 10px; font-size: 10px; color: #444; }
            .divider { border-top: 1px dashed #000; margin: 5px 0; }
            .totals { margin-bottom: 10px; }
            .total-row { display: flex; justify-content: space-between; font-weight: bold; font-size: 14px; margin-top: 5px; }
            .negative { color: #cc0000; }
            .footer { text-align: center; font-size: 10px; margin-top: 10px; border-top: 1px dashed #000; padding-top: 5px; }
            @media print { body { width: 100%; } @page { margin: 0; size: 80mm auto; } }
        `;

        const lines = returnData.lines || [];
        const itemsHtml = lines.map(line => `
            <div style="margin-bottom: 5px;">
                <div class="row item-name">
                    <span>${line.product_name || line.product?.name || t('receipt.unknown_product')}</span>
                    <span>x ${line.quantity_returned}</span>
                </div>
                <div class="row item-details">
                    <span>@ ${formatMoney(line.unit_price)}</span>
                    <span class="negative">-${formatMoney(line.refund_amount)}</span>
                </div>
            </div>
        `).join('');

        const totalRefund = lines.reduce((s, l) => s + Number(l.refund_amount || 0), 0);
        const restockingFee = Number(returnData.restocking_fee || 0);
        const netRefund = totalRefund - restockingFee;
        const customerName = returnData.originalOrder?.customer?.name || returnData.customer?.name || t('receipt.customer');
        const originalOrderNumber = returnData.originalOrder?.order_number || returnData.original_order_number || '—';

        return `
            <!DOCTYPE html>
            <html>
            <head><meta charset="utf-8"><title>${t('receipt.return_title')} ${returnData.return_number}</title><style>${returnStyles}</style></head>
            <body>
                <div class="header">
                    <div class="return-title">${t('receipt.return_title')}</div>
                    <div>${t('receipt.title')}</div>
                    <div class="meta">
                        <div>${t('receipt.date')}: ${formatDate(returnData.return_date)}</div>
                        <div>${t('receipt.return_number')}: ${returnData.return_number}</div>
                        <div>${t('receipt.orig_order')}: ${originalOrderNumber}</div>
                        <div>${t('receipt.customer')}: ${customerName}</div>
                        <div>${t('receipt.refund_method')}: ${(returnData.refund_method || 'cash').replace('_', ' ').toUpperCase()}</div>
                    </div>
                </div>
                <div class="items">${itemsHtml}</div>
                <div class="divider"></div>
                <div class="totals">
                    <div class="row"><span>${t('receipt.subtotal_refund')}</span><span class="negative">-${formatMoney(totalRefund)}</span></div>
                    ${restockingFee > 0 ? `<div class="row"><span>${t('receipt.restocking_fee')}</span><span>+${formatMoney(restockingFee)}</span></div>` : ''}
                    <div class="total-row"><span>${t('receipt.net_refund')}</span><span class="negative">-${formatMoney(netRefund)}</span></div>
                </div>
                <div class="divider"></div>
                <div class="footer">
                    <div>${t('receipt.return_footer')}</div>
                    <div style="margin-top:5px;font-size:9px;">${t('receipt.reason')}: ${returnData.return_reason || t('receipt.not_specified')}</div>
                    <div style="margin-top:10px;font-size:8px;">${returnData.uuid || ''}</div>
                </div>
            </body>
            </html>
        `;
    };

    const printReturnReceipt = async (returnData) => {
        try {
            const html = await generateReturnReceiptHtml(returnData);
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.style.position = 'fixed';
            iframe.style.right = '0';
            iframe.style.bottom = '0';
            document.body.appendChild(iframe);
            const doc = iframe.contentDocument || iframe.contentWindow.document;
            doc.open();
            doc.write(html);
            doc.close();
            iframe.onload = () => {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
                setTimeout(() => { document.body.removeChild(iframe); }, 2000);
            };
        } catch (error) {
            console.error('Failed to print return receipt:', error);
            alert('Could not print return receipt. Please try again.');
        }
    };

    return { generateReceiptHtml, printReceipt, generateReturnReceiptHtml, printReturnReceipt };
}
