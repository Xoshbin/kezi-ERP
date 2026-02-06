<?php

namespace Kezi\Foundation\Database\Seeders;

use Illuminate\Database\Seeder;
use Kezi\Foundation\Models\Currency;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            [
                'code' => 'USD',
                'name' => ['en' => 'US Dollar', 'ckb' => 'دۆلاری ئەمریکی', 'ar' => 'دولار أمريكي'],
                'symbol' => '$',
                'decimal_places' => 2,
            ],
            [
                'code' => 'EUR',
                'name' => ['en' => 'Euro', 'ckb' => 'یۆرۆ', 'ar' => 'يورو'],
                'symbol' => '€',
                'decimal_places' => 2,
            ],
            [
                'code' => 'GBP',
                'name' => ['en' => 'British Pound', 'ckb' => 'پاوەنی بەریتانی', 'ar' => 'جنيه إسترليني'],
                'symbol' => '£',
                'decimal_places' => 2,
            ],
            [
                'code' => 'JPY',
                'name' => ['en' => 'Japanese Yen', 'ckb' => 'یەنی ژاپۆنی', 'ar' => 'ين ياباني'],
                'symbol' => '¥',
                'decimal_places' => 0,
            ],
            [
                'code' => 'IQD',
                'name' => ['en' => 'Iraqi Dinar', 'ckb' => 'دیناری عێراقی', 'ar' => 'دينار عراقي'],
                'symbol' => 'ع.د',
                'decimal_places' => 3,
            ],
            [
                'code' => 'SAR',
                'name' => ['en' => 'Saudi Riyal', 'ckb' => 'ڕیاڵی سعوودی', 'ar' => 'ريال سعودي'],
                'symbol' => 'ر.س',
                'decimal_places' => 2,
            ],
            [
                'code' => 'AED',
                'name' => ['en' => 'UAE Dirham', 'ckb' => 'دیرهەمی ئیماراتی', 'ar' => 'درهم إماراتي'],
                'symbol' => 'د.إ',
                'decimal_places' => 2,
            ],
            [
                'code' => 'TRY',
                'name' => ['en' => 'Turkish Lira', 'ckb' => 'لیرەی تورکی', 'ar' => 'ليرة تركية'],
                'symbol' => '₺',
                'decimal_places' => 2,
            ],
            [
                'code' => 'IRR',
                'name' => ['en' => 'Iranian Rial', 'ckb' => 'ڕیاڵی ئێرانی', 'ar' => 'ريال إيراني'],
                'symbol' => '﷼',
                'decimal_places' => 2,
            ],
            [
                'code' => 'KWD',
                'name' => ['en' => 'Kuwaiti Dinar', 'ckb' => 'دیناری کوەیتی', 'ar' => 'دينار كويتي'],
                'symbol' => 'د.ك',
                'decimal_places' => 3,
            ],
            [
                'code' => 'BHD',
                'name' => ['en' => 'Bahraini Dinar', 'ckb' => 'دیناری بەحرەینی', 'ar' => 'دينار بحريني'],
                'symbol' => '.د.ب',
                'decimal_places' => 3,
            ],
            [
                'code' => 'OMR',
                'name' => ['en' => 'Omani Rial', 'ckb' => 'ڕیاڵی عومانی', 'ar' => 'ريال عماني'],
                'symbol' => 'ر.ع.',
                'decimal_places' => 3,
            ],
            [
                'code' => 'QAR',
                'name' => ['en' => 'Qatari Riyal', 'ckb' => 'ڕیاڵی قەتەری', 'ar' => 'ريال قطري'],
                'symbol' => 'ر.ق',
                'decimal_places' => 2,
            ],
            [
                'code' => 'JOD',
                'name' => ['en' => 'Jordanian Dinar', 'ckb' => 'دیناری ئوردنی', 'ar' => 'دينار أردني'],
                'symbol' => 'د.ا',
                'decimal_places' => 3,
            ],
            [
                'code' => 'EGP',
                'name' => ['en' => 'Egyptian Pound', 'ckb' => 'جونەیھـی میسری', 'ar' => 'جنيه مصري'],
                'symbol' => '£',
                'decimal_places' => 2,
            ],
            [
                'code' => 'LBP',
                'name' => ['en' => 'Lebanese Pound', 'ckb' => 'لیرەی لوبنانی', 'ar' => 'ليرة لبنانية'],
                'symbol' => 'ل.ل',
                'decimal_places' => 2,
            ],
            [
                'code' => 'SYP',
                'name' => ['en' => 'Syrian Pound', 'ckb' => 'لیرەی سووری', 'ar' => 'ليرة سورية'],
                'symbol' => '£',
                'decimal_places' => 2,
            ],
            [
                'code' => 'CAD',
                'name' => ['en' => 'Canadian Dollar', 'ckb' => 'دۆلاری کەنەدی', 'ar' => 'دولار كندي'],
                'symbol' => '$',
                'decimal_places' => 2,
            ],
            [
                'code' => 'AUD',
                'name' => ['en' => 'Australian Dollar', 'ckb' => 'دۆلاری ئوسترالی', 'ar' => 'دولار أسترالي'],
                'symbol' => '$',
                'decimal_places' => 2,
            ],
            [
                'code' => 'CHF',
                'name' => ['en' => 'Swiss Franc', 'ckb' => 'فرانکی سویسری', 'ar' => 'فرنك سويسري'],
                'symbol' => 'CHF',
                'decimal_places' => 2,
            ],
            [
                'code' => 'CNY',
                'name' => ['en' => 'Chinese Yuan', 'ckb' => 'یوانی چینی', 'ar' => 'يوان صيني'],
                'symbol' => '¥',
                'decimal_places' => 2,
            ],
            [
                'code' => 'INR',
                'name' => ['en' => 'Indian Rupee', 'ckb' => 'ڕوپیەی هیندی', 'ar' => 'روبية هندية'],
                'symbol' => '₹',
                'decimal_places' => 2,
            ],
            [
                'code' => 'RUB',
                'name' => ['en' => 'Russian Ruble', 'ckb' => 'ڕۆبڵی ڕووسی', 'ar' => 'روبل روسي'],
                'symbol' => '₽',
                'decimal_places' => 2,
            ],
            [
                'code' => 'BRL',
                'name' => ['en' => 'Brazilian Real', 'ckb' => 'ڕیاڵی بەڕازیلی', 'ar' => 'ريال برازيلي'],
                'symbol' => 'R$',
                'decimal_places' => 2,
            ],
            [
                'code' => 'ZAR',
                'name' => ['en' => 'South African Rand', 'ckb' => 'ڕاندی ئەفریقای باشوور', 'ar' => 'راند جنوب أفريقيا'],
                'symbol' => 'R',
                'decimal_places' => 2,
            ],
            [
                'code' => 'KRW',
                'name' => ['en' => 'South Korean Won', 'ckb' => 'وۆنی کۆریای باشوور', 'ar' => 'وون كوري جنوبي'],
                'symbol' => '₩',
                'decimal_places' => 0,
            ],
            [
                'code' => 'SGD',
                'name' => ['en' => 'Singapore Dollar', 'ckb' => 'دۆلاری سەنگافوورە', 'ar' => 'دولار سنغافوري'],
                'symbol' => '$',
                'decimal_places' => 2,
            ],
            [
                'code' => 'HKD',
                'name' => ['en' => 'Hong Kong Dollar', 'ckb' => 'دۆلاری هۆنگ کۆنگ', 'ar' => 'دولار هونغ كونغ'],
                'symbol' => '$',
                'decimal_places' => 2,
            ],
            [
                'code' => 'NZD',
                'name' => ['en' => 'New Zealand Dollar', 'ckb' => 'دۆلاری نیوزلەندی', 'ar' => 'دولار نيوزيلندي'],
                'symbol' => '$',
                'decimal_places' => 2,
            ],
            [
                'code' => 'MXN',
                'name' => ['en' => 'Mexican Peso', 'ckb' => 'پیسۆی مەکسیکی', 'ar' => 'بيزو مكسيكي'],
                'symbol' => '$',
                'decimal_places' => 2,
            ],
            [
                'code' => 'IDR',
                'name' => ['en' => 'Indonesian Rupiah', 'ckb' => 'ڕوپیەی ئەندۆنیزی', 'ar' => 'روبية إندونيسية'],
                'symbol' => 'Rp',
                'decimal_places' => 2,
            ],
            [
                'code' => 'MYR',
                'name' => ['en' => 'Malaysian Ringgit', 'ckb' => 'ڕینگێتی مالیزی', 'ar' => 'رينغيت ماليزي'],
                'symbol' => 'RM',
                'decimal_places' => 2,
            ],
            [
                'code' => 'PHP',
                'name' => ['en' => 'Philippine Peso', 'ckb' => 'پیسۆی فلیپینی', 'ar' => 'بيزو فلبيني'],
                'symbol' => '₱',
                'decimal_places' => 2,
            ],
            [
                'code' => 'THB',
                'name' => ['en' => 'Thai Baht', 'ckb' => 'باتی تایلەندی', 'ar' => 'بات تايلاندي'],
                'symbol' => '฿',
                'decimal_places' => 2,
            ],
            [
                'code' => 'VND',
                'name' => ['en' => 'Vietnamese Dong', 'ckb' => 'دۆنگی ڤێتنامی', 'ar' => 'دونغ فيتنامي'],
                'symbol' => '₫',
                'decimal_places' => 0,
            ],
            [
                'code' => 'TWD',
                'name' => ['en' => 'New Taiwan Dollar', 'ckb' => 'دۆلاری نوێی تایوان', 'ar' => 'دولار تايواني جديد'],
                'symbol' => 'NT$',
                'decimal_places' => 2,
            ],
            [
                'code' => 'PKR',
                'name' => ['en' => 'Pakistani Rupee', 'ckb' => 'ڕوپیەی پاکستانی', 'ar' => 'روبية باكستانية'],
                'symbol' => '₨',
                'decimal_places' => 2,
            ],
            [
                'code' => 'NGN',
                'name' => ['en' => 'Nigerian Naira', 'ckb' => 'نایرای نێجیری', 'ar' => 'نايرا نيجيرية'],
                'symbol' => '₦',
                'decimal_places' => 2,
            ],
            [
                'code' => 'ARS',
                'name' => ['en' => 'Argentine Peso', 'ckb' => 'پیسۆی ئەرجەنتینی', 'ar' => 'بيزو أرجنتيني'],
                'symbol' => '$',
                'decimal_places' => 2,
            ],
            [
                'code' => 'CLP',
                'name' => ['en' => 'Chilean Peso', 'ckb' => 'پیسۆی چیلی', 'ar' => 'بيزو شيلي'],
                'symbol' => '$',
                'decimal_places' => 0,
            ],
            [
                'code' => 'COP',
                'name' => ['en' => 'Colombian Peso', 'ckb' => 'پیسۆی کۆلۆمبی', 'ar' => 'بيزو كولومبي'],
                'symbol' => '$',
                'decimal_places' => 2,
            ],
            [
                'code' => 'PEN',
                'name' => ['en' => 'Peruvian Sol', 'ckb' => 'سۆڵی پیرۆیی', 'ar' => 'سول بيروفي'],
                'symbol' => 'S/.',
                'decimal_places' => 2,
            ],
            [
                'code' => 'ILS',
                'name' => ['en' => 'Israeli New Shekel', 'ckb' => 'شیکڵی ئیسرائیلی', 'ar' => 'شيكل إسرائيلي جديد'],
                'symbol' => '₪',
                'decimal_places' => 2,
            ],
            [
                'code' => 'PLN',
                'name' => ['en' => 'Polish Zloty', 'ckb' => 'زلوتی پۆڵەندی', 'ar' => 'زلوتي بولندي'],
                'symbol' => 'zł',
                'decimal_places' => 2,
            ],
            [
                'code' => 'SEK',
                'name' => ['en' => 'Swedish Krona', 'ckb' => 'کرۆنای سویدی', 'ar' => 'كرونة سويدية'],
                'symbol' => 'kr',
                'decimal_places' => 2,
            ],
            [
                'code' => 'NOK',
                'name' => ['en' => 'Norwegian Krone', 'ckb' => 'کرۆنای نەرویجی', 'ar' => 'كرونة نرويجية'],
                'symbol' => 'kr',
                'decimal_places' => 2,
            ],
            [
                'code' => 'DKK',
                'name' => ['en' => 'Danish Krone', 'ckb' => 'کرۆنای دانیمارکی', 'ar' => 'كرونة دنماركية'],
                'symbol' => 'kr',
                'decimal_places' => 2,
            ],
            [
                'code' => 'CZK',
                'name' => ['en' => 'Czech Koruna', 'ckb' => 'کۆرۆنای چیکی', 'ar' => 'كرونة تشيكية'],
                'symbol' => 'Kč',
                'decimal_places' => 2,
            ],
            [
                'code' => 'HUF',
                'name' => ['en' => 'Hungarian Forint', 'ckb' => 'فۆرێنتی هەنگاری', 'ar' => 'فورينت مجري'],
                'symbol' => 'Ft',
                'decimal_places' => 2,
            ],
            [
                'code' => 'RON',
                'name' => ['en' => 'Romanian Leu', 'ckb' => 'لێوی ڕۆمانی', 'ar' => 'ليو روماني'],
                'symbol' => 'lei',
                'decimal_places' => 2,
            ],
            [
                'code' => 'UAH',
                'name' => ['en' => 'Ukrainian Hryvnia', 'ckb' => 'گریڤنای ئۆکرانی', 'ar' => 'هريفنا أوكرانية'],
                'symbol' => '₴',
                'decimal_places' => 2,
            ],
            [
                'code' => 'MAD',
                'name' => ['en' => 'Moroccan Dirham', 'ckb' => 'دیرهەمی مەغریبی', 'ar' => 'درهم مغربي'],
                'symbol' => 'د.م.',
                'decimal_places' => 2,
            ],
            [
                'code' => 'TND',
                'name' => ['en' => 'Tunisian Dinar', 'ckb' => 'دیناری تونسی', 'ar' => 'دينار تونسي'],
                'symbol' => 'د.ت',
                'decimal_places' => 3,
            ],
            [
                'code' => 'DZD',
                'name' => ['en' => 'Algerian Dinar', 'ckb' => 'دیناری جەزائیری', 'ar' => 'دينار جزائري'],
                'symbol' => 'د.ج',
                'decimal_places' => 2,
            ],
            [
                'code' => 'YER',
                'name' => ['en' => 'Yemeni Rial', 'ckb' => 'ڕیاڵی یەمەنی', 'ar' => 'ريال يمني'],
                'symbol' => '﷼',
                'decimal_places' => 2,
            ],
            [
                'code' => 'LYD',
                'name' => ['en' => 'Libyan Dinar', 'ckb' => 'دیناری لیبی', 'ar' => 'دينار ليبي'],
                'symbol' => 'ل.د',
                'decimal_places' => 3,
            ],
            [
                'code' => 'SDG',
                'name' => ['en' => 'Sudanese Pound', 'ckb' => 'پاوەنی سوودانی', 'ar' => 'جنيه سوداني'],
                'symbol' => '£',
                'decimal_places' => 2,
            ],
            [
                'code' => 'AFN',
                'name' => ['en' => 'Afghan Afghani', 'ckb' => 'ئەفغانی ئەفغانستان', 'ar' => 'أفغاني أفغاني'],
                'symbol' => '؋',
                'decimal_places' => 2,
            ],
            [
                'code' => 'BDT',
                'name' => ['en' => 'Bangladeshi Taka', 'ckb' => 'تاکای بەنگلادیشی', 'ar' => 'تاكا بنغلاديشي'],
                'symbol' => '৳',
                'decimal_places' => 2,
            ],
            [
                'code' => 'LKR',
                'name' => ['en' => 'Sri Lankan Rupee', 'ckb' => 'ڕوپیەی سریلانکی', 'ar' => 'روبية سريلانكية'],
                'symbol' => '₨',
                'decimal_places' => 2,
            ],
            [
                'code' => 'ETB',
                'name' => ['en' => 'Ethiopian Birr', 'ckb' => 'بیڕی ئەسیوپی', 'ar' => 'بير إثيوبي'],
                'symbol' => 'Br',
                'decimal_places' => 2,
            ],
            [
                'code' => 'GHS',
                'name' => ['en' => 'Ghanaian Cedi', 'ckb' => 'سیدی گانی', 'ar' => 'سيدي غاني'],
                'symbol' => '₵',
                'decimal_places' => 2,
            ],
            [
                'code' => 'KES',
                'name' => ['en' => 'Kenyan Shilling', 'ckb' => 'شلنی کینی', 'ar' => 'شلن كيني'],
                'symbol' => 'KSh',
                'decimal_places' => 2,
            ],
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(
                ['code' => $currency['code']],
                [
                    'name' => $currency['name'],
                    'symbol' => $currency['symbol'],
                    'is_active' => true,
                    'decimal_places' => $currency['decimal_places'],
                ]
            );
        }
    }
}
