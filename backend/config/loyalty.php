<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cashback Percentage (fallback)
    |--------------------------------------------------------------------------
    | Applied when the user holds no badge yet (new customers).
    | Badge-specific rates in the badges table override this.
    */
    'cashback_percent' => (float) env('LOYALTY_CASHBACK_PERCENT', 5),
];
