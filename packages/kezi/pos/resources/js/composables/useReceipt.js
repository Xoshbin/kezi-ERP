
import { db } from '../db/pos-db';

export function useReceipt() {

    const generateReceiptHtml = async (orderId) => {
        // 1. Fetch Data
        const order = await db.orders.get(orderId);
        if (!order) throw new Error(`Order ${orderId} not found`);

        const lines = await db.order_lines.where('order_id').equals(orderId).toArray();
        
        // Enhance lines with product names
        for (const line of lines) {
            const product = await db.products.get(line.product_id);
            line.product_name = product?.name || 'Unknown Product';
        }

        // Get Customer
        let customer = null;
        if (order.customer_id) {
            customer = await db.customers.get(order.customer_id);
        }

        // Get Currency
        const currencySetting = await db.settings.get('company_currency');
        const currencyCode = currencySetting?.value?.code || 'USD';
        
        // Helper for currency formatting
        const formatMoney = (amount) => {
            if (amount === undefined || amount === null) return '0.00';
            const val = Number(amount) / 100;
            return new Intl.NumberFormat('en-US', { style: 'currency', currency: currencyCode }).format(val);
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
                        <span>Line Discount</span>
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
                <title>Receipt ${order.order_number}</title>
                <style>${styles}</style>
            </head>
            <body>
                <div class="header">
                    <div class="title">KEZI UNIVERSAL POS</div>
                    <div>Receipt</div>
                    <div class="meta">
                        <div>Date: ${formatDate(order.ordered_at)}</div>
                        <div>Order: ${order.order_number}</div>
                        <div>Session: #${order.pos_session_id}</div>
                        ${customer ? `<div>Customer: ${customer.name}</div>` : ''}
                    </div>
                </div>

                <div class="items">
                    ${itemsHtml}
                </div>

                <div class="divider"></div>

                <div class="totals">
                    <div class="row">
                        <span>Subtotal</span>
                        <span>${formatMoney(lines.reduce((sum, l) => sum + (l.unit_price * l.quantity), 0))}</span>
                    </div>
                    ${order.discount_amount > 0 ? `
                        <div class="row" style="font-weight: bold;">
                            <span>Discount</span>
                            <span>-${formatMoney(order.discount_amount)}</span>
                        </div>
                    ` : ''}
                    <div class="row">
                        <span>Tax</span>
                        <span>${formatMoney(order.total_tax)}</span>
                    </div>
                    <div class="total-row">
                        <span>TOTAL</span>
                        <span>${formatMoney(order.total_amount)}</span>
                    </div>
                </div>

                <div class="divider"></div>

                <div class="payment">
                    <div class="row">
                        <span>Method</span>
                        <span style="text-transform: capitalize">${order.payment_method}</span>
                    </div>
                    <div class="row">
                        <span>Tendered</span>
                        <span>${formatMoney(order.amount_tendered)}</span>
                    </div>
                    <div class="row">
                        <span>Change</span>
                        <span>${formatMoney(order.change_given)}</span>
                    </div>
                </div>

                <div class="footer">
                    <div>Thank you for your purchase!</div>
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

    return { generateReceiptHtml, printReceipt };
}
