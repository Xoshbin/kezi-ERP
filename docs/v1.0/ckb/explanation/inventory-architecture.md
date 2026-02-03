---
title: تەلارسازی کۆگا
icon: heroicon-o-server-stack
order: 10
---

# ڕوونکردنەوە: تەلارسازی کۆگا

ئەم ڕێبەرە تەکنیکییە تەلارسازی ناوەکی سیستمی کۆگا ڕوون دەکاتەوە، بە سەرنج خستنە سەر مۆدێلەکانی داتا، چەمکی "جووڵە"، و جێبەجێکردنی هاوکات (synchronous processing).

---

## وێنە گەورەکە

سیستمی کۆگا وەڵامی چوار پرسیاری سەرەکی دەداتەوە:

1.  **چەندمان هەیە؟** ← بڕی کۆگا (`StockQuant`)
2.  **لە کوێیە؟** ← شوێنەکان (`StockLocation`)
3.  **چۆن دەجووڵێت؟** ← جووڵەکانی کۆگا (`StockMove`)
4.  **تێچووی چییە؟** ← هەڵسەنگاندن (`InventoryCostLayer`, `Product.average_cost`)

---

## بەشە سەرەکییەکان (مۆدێلەکان)

### 1. StockQuant - "ژمێرەرەکە"
**Namespace**: `Modules\Inventory\App\Models\StockQuant`

ئەمە "وێنەی ئێستای کۆگا"یە. بڕی بەرهەمێکی دیاریکراو لە شوێنێکی دیاریکراو دەپێوێت:

| Product | Location | Quantity | Reserved | Available |
| :--- | :--- | :--- | :--- | :--- |
| iPhone 15 | Warehouse A | 100 | 10 | 90 |

*   **نرخی هەژمارکراو**: `Available = quantity - reserved_quantity`

### 2. StockMove - "تۆماری مامەڵە"
**Namespace**: `Modules\Inventory\App\Models\StockMove`

هەر جارێک کۆگا دەجووڵێت، تۆمارێکی `StockMove` دروست دەبێت.
*   **ڕەوتی کار (Status Workflow)**: `Draft` → `Confirmed` → `Done`

### 3. StockLocation - "شوێنەکان"
**Namespace**: `Modules\Inventory\App\Models\StockLocation`

*   **Internal**: کۆگاکانی خۆت.
*   **Vendor**: شوێنی کڕین (مەجازی).
*   **Customer**: شوێنی فرۆشتن (مەجازی).
*   **Inventory Adjustment**: بۆ چاکسازییەکان (مەجازی).

---

## تەلارسازی جێبەجێکردنی هاوکات (Synchronous Processing)

کاتێک جووڵەیەکی کۆگا پەسەند دەکرێت (دۆخ دەبێتە `Done`)، سیستمەکە نوێکارییەکانی کۆگا و ژمێریاری **بە شێوەی هاوکات** لە هەمان مامەڵەی دیتابەیس (Transaction) جێبەجێ دەکات.

### ڕەوتی هاتنەژوورەوە (Incoming Stock Flow)
```
User Confirms Move
    │
    ▼
StockMoveObserver::updated()
    │
    ▼ (Same DB Transaction)
[ProcessIncomingStockAction]
    ├─ Extract Cost (from PO/Bill)
    ├─ Update Valuation (Cost Layers / AVCO)
    ├─ Update Quantities (StockQuant)
    └─ Create Journal Entry (Asset vs Input)
```

### ڕەوتی چوونەدەرەوە (Outgoing Stock Flow)
```
User Confirms Move
    │
    ▼
StockMoveObserver::updated()
    │
    ▼ (Same DB Transaction)
[ProcessOutgoingStockAction]
    ├─ Calculate COGS (FIFO/LIFO/AVCO)
    ├─ Update Valution (Consume Layers)
    ├─ Update Quantities (Decrease StockQuant)
    └─ Create Journal Entry (COGS vs Asset)
```

**بۆچی هاوکات؟**
*   **دروستی داتا**: بڕی کۆگا و نرخە ژمێریارییەکان دەبێت هەمیشە یەکسان بن.
*   **کۆنترۆڵی هەڵە**: ئەگەر پشتڕاستکردنەوەی تێچوو شکست بێنێت (نموونە: کۆگای نێگەتیڤ)، هەموو ترانزاکشنەکە هەڵدەوەشێتەوە (Rollback).

---

## چاودێرەکان و چالاکییەکان (Observers & Actions)

*   **`StockMoveObserver`**: خاڵی دەستپێکی لۆجیکی جووڵەیە. گۆڕانی دۆخ بۆ `Done` دەناسێتەوە و کارەکە دەدات بە Actionـی گونجاو.
*   **`StockQuantService`**: بەرپرسە لە قوفڵکردنی ئەتۆمی (`lockForUpdate`) و نوێکردنەوەی ژمێرەرەکانی بڕ.
*   **`InventoryValuationService`**: بیرکاریی ئاڵۆزی چینەکانی FIFO و تێچووی ناوەند (Moving Average) بەڕێوە دەبات.

---

## سەیری ئەمانەش بکە
*   [سەرچاوە: کێڵگەکانی کۆگا](../reference/inventory-fields.md)
