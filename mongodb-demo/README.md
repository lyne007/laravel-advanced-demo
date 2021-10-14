# 用mongodb完成的短链接demo

- redis 计数器
- mongodb存储短链接
- 用62进制来表示短链接

## 安装mongodb包
```shell
composer require jenssegers/mongodb="3.8.x"
```

在 config/database.php 的数组 connections 添加配置：
```shell
'mongodb' => [
    'driver' => 'mongodb',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', 27017),
    'database' => env('DB_DATABASE', 'homestead'),
    'username' => env('DB_USERNAME', 'homestead'),
    'password' => env('DB_PASSWORD', 'secret'),
    'options' => [
        'database' => env('DB_AUTHENTICATION_DATABASE', 'admin'), // required with Mongo 3+
    ],
],
```
这样就可以使用了
```php
DB::connection('mongodb')
    ->collection('test')
    ->get();
DB::connection('mongodb')
    ->collection('test')
    ->where('id', 1)
    ->first();
DB::connection('mongodb')
    ->collection('test')
    ->where('id', 1)
    ->update(['name'=>'test']);
```
## 计数器
> 每次生成短链接+1
```php
$num = Redis::incr('cnt');

```
## 转62进制码
```php
public function num2code($num)
    {
        // 62进制序列表
        $codeTable = [
            0 => "d",
            1 => "G",
            2 => "1",
            3 => "g",
            4 => "e",
            5 => "9",
            6 => "h",
            7 => "V",
            8 => "D",
            9 => "2",
            10 => "P",
            11 => "w",
            12 => "8",
            13 => "H",
            14 => "M",
            15 => "q",
            16 => "W",
            17 => "J",
            18 => "X",
            19 => "s",
            20 => "6",
            21 => "F",
            22 => "E",
            23 => "S",
            24 => "Y",
            25 => "n",
            26 => "i",
            27 => "h",
            28 => "y",
            29 => "I",
            30 => "N",
            31 => "x",
            32 => "m",
            33 => "p",
            34 => "5",
            35 => "0",
            36 => "7",
            37 => "b",
            38 => "c",
            39 => "o",
            40 => "C",
            41 => "i",
            42 => "U",
            43 => "T",
            44 => "z",
            45 => "v",
            46 => "Q",
            47 => "3",
            48 => "f",
            49 => "4",
            50 => "K",
            51 => "O",
            52 => "a",
            53 => "B",
            54 => "I",
            55 => "k",
            56 => "Z",
            57 => "u",
            58 => "A",
            59 => "t",
            60 => "j",
            61 => "H",
        ];
        $code = '';
        while ($num > 61) {
            $code = $codeTable[($num % 62)] . $code;
            $num = floor($num / 62);
        }
        if ($num > 0) {
            $code = $codeTable[$num] . $code;
        }
        return $code;
    }
```


## 效果
![Alt text](https://github.com/lyne007/laravel-advanced-demo/blob/master/imgs/mongodb-demo.gif?raw=true)


