<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Item;
use App\Models\Currency;

class ItemController extends Controller
{
    public function getItems()
    {
        $start_time = microtime(true);
      
        $items = Item::where('category', 3)
            ->limit(10)
            ->get();

        $latestCurrencies = Currency::whereIn('currency', ['EUR', 'USD'])
            ->orderBy('date', 'desc')
            ->limit(2)
            ->get()
            ->keyBy('currency');

        foreach ($items as $item) {
            if ($latestCurrencies->has($item->currency)) {
                $latestCurrency = $latestCurrencies[$item->currency];
                $item->priceRUB = $item->price * $latestCurrency->value;
                $item->dateCurrency = $latestCurrency->date;
            }
        }

        $end_time = microtime(true);

        return response()->json([
            'time' => $end_time - $start_time,
            'result' => $items->toArray(),
        ]);
    }


    public function getItemsWithCurrency()
    {
        $start_time = microtime(true);

        $items = Item::select([
                'items.*',
                DB::raw('items.price * currency.value as priceRUB'),
                'currency.date as dateCurrency'
            ])
            ->join(DB::raw('(SELECT currency, MAX(date) as date FROM currency GROUP BY currency) as latest'), function ($join) {
                $join->on('items.currency', '=', 'latest.currency');
            })
            ->join('currency', function ($join) {
                $join->on('latest.currency', '=', 'currency.currency');
                $join->on('latest.date', '=', 'currency.date');
            })
            ->where('items.category', 3)
            ->take(10)
            ->get();

        $end_time = microtime(true);

        return response()->json([
            'time' => $end_time - $start_time,
            'result' => $items->toArray(),
        ]);
    }
}
