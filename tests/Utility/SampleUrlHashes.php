<?php
/**
 * Created by PhpStorm.
 * User: Daniel
 * Date: 22.02.2019
 * Time: 15:34
 */

namespace HashedUriTest\Utility;


class SampleUrlHashes
{

    /**
     * @return array An array containing pairs of URL and hash (assuming that the full URL was hashed) with SHA256 and
     *               key "secure-key".
     */
    public static function getSampleHashes()
    {
        return [
            ['http://example.com', '435571dfde2d93b7a7cbbe6b198e21b4df78746f8ca31a5b2e4442761cfdfbe4'],
            ['http://example.com?foo=bar', '9d877065e5ffba5ceccc6d91de98ff5213bde2905517905a2770d6db9b30a6fb'],
            ['http://example.com/', '959d0519b205e74218f4c4c76a7ae0a712f4f59f3085c483b74db9ac31f4b593'],
            ['http://example.com/foo', '2808c9d8b7e962eaf8a6eab96935c4b91033745008888242ede74fc32bf80a8c'],
            ['http://example.com/?foo', '52ff06b63ccf19cb3152ee0e16674ebaa9d5c1e06c5081faf08c97b18fddcbff'],
            ['http://example.com/foo?bar', '1c09187104eeb827716e3dea6a16f785ea290f576b4cdf133b528d32d37e8290'],
            ['http://example.com/foo?bar=', '33c82515dee1ca31b2f376c537b3f404308f32ec4a72ffdb42268a682dda0c2a'],
            ['http://example.com/foo?bar=baz', 'e268ab02cbc305e01b02d1a98b89d06841db10b2ea731decc0d79cbfbf176487'],
            ['http://example.com/foo?bar=baz&qux=pax', 'd98ec836a83d7a6d3c0caefdce66e4335ee3f7a1ddd9f3aecbd8f07e9c2537fa'],
            ['https://example.com', '79676d8d4a44790926d547277bf8592362a8ab2b8f734faea751b6a316ec010b'],
            ['https://example.com?foo=bar', '76b6bc31ff11c0199f33f749e0fb6111ccdd8b8e509a2de40a491e05f3b12613'],
            ['https://example.com/', 'c8a21388ce96ed7f0a0e70446ff25977b81ab0377ef934ba990e32f71cc9ca18'],
            ['https://example.com/foo', 'dbc99873a5c7ddaccfa93f9911809f3563bd61f717767919247ab02905d5dc9b'],
            ['https://example.com/?foo', 'e4ed4572fffbfc855adeb281ce33104d0134a573ec8f93139f087eebdae73f37'],
            ['https://example.com/foo?bar', '7b6b09f25743cb8ff9d63697faffba0029a14afb9573d9a0132f4e8588b94918'],
            ['https://example.com/foo?bar=', 'b90b9f8c17d95de7716f8ccfc36222654d768bcd9388723dc69fe2aeaa3436c6'],
            ['https://example.com/foo?bar=baz', '1c99ed0664730e8b726b6bdf6d44d60fb70c7c19fa8acc6ec5a305bbe0281db4'],
            ['https://example.com/foo?bar=baz&qux=pax', 'ba0b8015aa7288fdb1182c21067956ebcf4ddf7ec5823d1eca4165d730d39fdb'],
            ['https://example.com/foo?bar=baz&qux=pax#fragment', 'b7a0878d12f7889d7f73d3df0db901df0e76198ff7e248027290c083722e3c14'],
            ['https://example.com:81/foo?bar=baz&qux=pax#fragment', '4bb519609fc2517b91c7fb7d1bccb3bf14c83ae0fe34c859c00a32926e65ff44'],
            ['https://subdomain.example.com:81/foo?bar=baz&qux=pax#fragment', '42e3f2c4405f4d88b22e1ee72d7be22da3f1268ca1016f0d0578d28dae06bf01'],
            ['relative/path/to/foo', '488a2ca689ae82ddf409bae55c0e365b7d4420803092f07e2acc9c7ff896ad11'],
            ['/foo?bar', '1a3f67f174bc3091412be2ab4031f0186fbe1043cb7b0e5de8ac316409a9999e'],
            ['/foo/an-unusal-long-uri-with-special%20characters?which&should=be_no_problem', 'ab8c844ffe7121e8501f47dbaf5b087d88b3ca56b208e59ba037c7ce615f3c0f'],
            ['/foo/?bar=qux', '6d707feee25e4dc579efbeda5b1f55c7f1eab228d33fa74444c39c887693bb6f'],
            ['/', 'd603a7eee64f1e0f9bc9388a7fdf18ebddab6c5676220b613a7f6f3c90a9ebfc'],
        ];
    }

    public function generateForOutput()
    {
        foreach([
                    'http://example.com',
                    'http://example.com?foo=bar',
                    'http://example.com/',
                    'http://example.com/foo',
                    'http://example.com/?foo',
                    'http://example.com/foo?bar',
                    'http://example.com/foo?bar=',
                    'http://example.com/foo?bar=baz',
                    'http://example.com/foo?bar=baz&qux=pax',
                    'https://example.com',
                    'https://example.com?foo=bar',
                    'https://example.com/',
                    'https://example.com/foo',
                    'https://example.com/?foo',
                    'https://example.com/foo?bar',
                    'https://example.com/foo?bar=',
                    'https://example.com/foo?bar=baz',
                    'https://example.com/foo?bar=baz&qux=pax',
                    'https://example.com/foo?bar=baz&qux=pax#fragment',
                    'https://example.com:81/foo?bar=baz&qux=pax#fragment',
                    'https://subdomain.example.com:81/foo?bar=baz&qux=pax#fragment',
                    'relative/path/to/foo',
                    '/foo?bar',
                    '/foo/an-unusal-long-uri-with-special%20characters?which&should=be_no_problem',
                    '/foo/?bar=qux',
                    '/'
                ] as $url) {

            printf("['%s', '%s'],<br>", $url, hash_hmac('SHA256', $url, 'secure-key'));
        }

    }

}