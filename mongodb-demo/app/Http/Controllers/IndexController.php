<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

//引用数据库

class IndexController extends Controller
{
    // 定义短链接短域名
    const SHORT_URL_HOST = 'http://mongodb.test/s/';

    public function index()
    {
        return view('index');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function getShortUrl(Request $request)
    {
        // 接收参数
        $original_url = $request->get('original_url');
        if (empty($original_url)) {
            return redirect('/')->withErrors('无效的地址')->withInput();
        }
        // 检验
        $short_url = $this->selectShortUrl($original_url);
        if ($short_url) {
            // 直接返回短链接
            return redirect('/')->withErrors('短链接：'.$short_url)->withInput();
        }
        // 利用redis简单做个计数器
        // Redis::set('cnt', 10000);
        $num = Redis::incr('cnt');
        // 用num转61进制码
        $code = $this->num2code($num);
        // 拼接短链接
        $short_url = self::SHORT_URL_HOST . $code;
        // 写入mongodb
        DB::connection('mongodb')
            ->collection('short_url')
            ->insert([
                'original_url'=>$original_url,
                'short_url'=>$short_url,
                'view_num'=>0
            ]);

        return redirect('/')->withErrors('短链接：'. $short_url)->withInput();
    }

    /**
     * @param $num
     * @return string
     */
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

    /**
     * @param $code
     */
    public function changeShortUrl($code)
    {
        $original_url = $this->selectOriginalUrl($code);
        if (!$original_url) {
            exit('无效的地址');
        }
        header('Location:' . $original_url, 302);
    }

    /**
     * @param $original_url
     * @return url|false
     */
    public function selectShortUrl($original_url)
    {
        $result = DB::connection('mongodb')
            ->collection('short_url')
            ->where(['original_url'=> $original_url])
            ->first();
        if ($result) {
            return $result['short_url'];
        } else {
            return false;
        }
    }

    /**
     * @param $code
     * @return url|false
     */
    public function selectOriginalUrl($code)
    {
        $res = DB::connection('mongodb')
            ->collection('short_url')
            ->where('short_url', self::SHORT_URL_HOST . $code)
            ->first();

        if ($res) {
            // 使用短链接访问的，访问量+1
            DB::connection('mongodb')
                ->collection('short_url')
                ->where('short_url', self::SHORT_URL_HOST . $code)
                ->increment('view_num');
            return $res['original_url'];
        } else {
            return false;
        }
    }

}



