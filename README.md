# Laravel Audit Log

### 描述：
Laravel專屬套件  
在Eloquent Model新增額外的update相關function  
使用這些function來讓model在更新或新增的時候  
可以自動產生log(array格式)  
並在需要的時候取出使用

### 使用Audit Log：
1. 在需要Audit Log的Model上
```php
use Hankz\LaravelAuditLog\Models\Traits\AuditLogGenerater;

class Order extends Model
{
    use AuditLogGenerater;
}
```

2. 複寫getAuditLogColumns()
格式為：
```php
[
  '<欄位名稱>' => [
    'title' => Callable|array|string,
    'value' => Callable|array
  ],
  ...
]
```
有寫的欄位名稱的欄位有異動才會紀錄log  
`title`與`value`可以省略不寫  
`title`預設回傳：`<欄位名稱>`  
`value`預設回傳：`<原始資料>`  

範例：
```php
public function getAuditLogColumns() {
    return [
        'user_id' => [
            'title' => '會員',
            'value'=> function ($userId, $order) {
                return optional(User::find($userId))->name;
            },
        ],
        'pay_status' => [
            'title' => '付款狀態',
            'value' => [
                0 => '未付款',
                1 => '已付款',
                2 => '已過期',
            ],
        ],
        'order_status' => [
            'title' => '訂單狀態',
            'value' => [
                0 => '取消',
                1 => '已成立',
                2 => '已付款',
                2 => '已出貨',
            ],,
        ],
        'total' => [
            'title' => '總計'
        ],
    ];
}
```

### Ｕpdate方式：

```php
$order->updateWithAuditLog([
    'pay_status' => 2,
    'order_status' => 0,
]);
```

或是：
```php
$order = app(Order::class)->updateOrCreateWithAuditLog([
    'user_id' => 1,
], [
    'pay_status' => 2,
    'order_status' => 0,
]);
```

### 取得Log：
```php
/**
 * return format:
 * {
 *   'create' => [
 *     '<欄位名稱>' => [
 *       'title' => '<欄位標題>',
 *       'new' => '<新值>',
 *     ],
 *   ],
 *   'update' => [
 *     '<欄位名稱>' => [
 *       'title' => '<欄位標題>',
 *       'old' => '<舊值>',
 *       'new' => '<新值>',
 *     ],
 *   ],
 *   'delete' => [
 *     '<欄位名稱>' => [
 *       'title' => '<欄位標題>',
 *       'old' => '<舊值>',
 *     ],
 *   ],
 * }
 */
$logs = $order->getLastAuditLogs();
```
也可以
```php
$order = Order::find(10);

$order->pay_status = 2;
$order->order_status = 0;

// logs here
$log = $order->generateAuditLogs();

$order->save();
```


另外還可以判斷是否有Log：
```php
if ($order->hasAuditLogs()) {
    // Do some thing...
}
```
