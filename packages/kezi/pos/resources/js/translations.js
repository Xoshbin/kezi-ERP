export const translations = {
    en: {
        receipt: {
            title: 'KEZI UNIVERSAL POS',
            header: 'Receipt',
            date: 'Date',
            order: 'Order',
            session: 'Session',
            customer: 'Customer',
            subtotal: 'Subtotal',
            discount: 'Discount',
            tax: 'Tax',
            total: 'TOTAL',
            method: 'Method',
            tendered: 'Tendered',
            change: 'Change',
            footer: 'Thank you for your purchase!',
            return_title: '★ RETURN RECEIPT ★',
            return_number: 'Return #',
            orig_order: 'Orig. Order',
            refund_method: 'Refund Method',
            subtotal_refund: 'Subtotal Refund',
            restocking_fee: 'Restocking Fee',
            net_refund: 'NET REFUND',
            return_footer: 'Thank you. We hope to serve you again.',
            reason: 'Reason',
            not_specified: 'Not specified',
            unknown_product: 'Unknown Product',
            line_discount: 'Line Discount'
        }
    },
    ckb: {
        receipt: {
            title: 'کێزی بۆ خاڵی فرۆشتن',
            header: 'وەسڵ',
            date: 'بەروار',
            order: 'داواکاری',
            session: 'خوول',
            customer: 'کڕیار',
            subtotal: 'کۆبەهای لاوەکی',
            discount: 'داشکاندن',
            tax: 'باج',
            total: 'کۆی گشتی',
            method: 'ڕێگای پارەدان',
            tendered: 'دراو',
            change: 'ماوە',
            footer: 'سوپاس بۆ کڕینەکەتان!',
            return_title: '★ وەسڵی گەڕاندنەوە ★',
            return_number: 'ژمارەی گەڕاندنەوە',
            orig_order: 'داواکاری بنەڕەتی',
            refund_method: 'ڕێگای گەڕاندنەوە',
            subtotal_refund: 'کۆی گەڕاندنەوە',
            restocking_fee: 'کرێی گەڕاندنەوە',
            net_refund: 'کۆی پاکتەی گەڕاندنەوە',
            return_footer: 'سوپاس. هیوادارین دووبارە بێنەوە لای ئێمە.',
            reason: 'هۆکار',
            not_specified: 'دیاری نەکراوە',
            unknown_product: 'بەرهەمی نەناسراو',
            line_discount: 'داشکاندنی ڕیز'
        }
    },
    ar: {
        receipt: {
            title: 'كيزي لنقطة البيع',
            header: 'فاتورة',
            date: 'التاريخ',
            order: 'الطلب',
            session: 'الجلسة',
            customer: 'العميل',
            subtotal: 'المجموع الفرعي',
            discount: 'الخصم',
            tax: 'الضريبة',
            total: 'المجموع الكلي',
            method: 'طريقة الدفع',
            tendered: 'المبلغ المدفوع',
            change: 'المبلغ المتبقي',
            footer: 'شكراً لشرائكم!',
            return_title: '★ إيصال الإرجاع ★',
            return_number: 'رقم الإرجاع',
            orig_order: 'الطلب الأصلي',
            refund_method: 'طريقة الاسترداد',
            subtotal_refund: 'مجموع الاسترداد',
            restocking_fee: 'رسوم إعادة التخزين',
            net_refund: 'صافي الاسترداد',
            return_footer: 'شكراً لكم. نأمل خدمتكم مرة أخرى.',
            reason: 'السبب',
            not_specified: 'غير محدد',
            unknown_product: 'منتج غير معروف',
            line_discount: 'خصم السطر'
        }
    }
};

export function getTranslation(key, locale = 'en') {
    const keys = key.split('.');
    let result = translations[locale] || translations['en'];
    
    for (const k of keys) {
        if (result && result[k]) {
            result = result[k];
        } else {
            // Fallback to English
            let fallback = translations['en'];
            for (const fk of keys) {
                if (fallback && fallback[fk]) {
                    fallback = fallback[fk];
                } else {
                    return key;
                }
            }
            return fallback;
        }
    }
    
    return result;
}
