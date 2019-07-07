<?php

namespace SNOWGIRL_CORE;

use pQuery;

class HtmlParser
{
    protected $adapter;

    protected static $instances = [];

    protected function __construct($content, $link = true)
    {
        $this->adapter = pQuery::parseStr($link ? $this->download($content) : $content);
    }

    protected function __clone()
    {
    }

    function download($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, "https://www.pornhub.com/");
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36");
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($ch, CURLOPT_HTTPHEADER, [
//            'cache-control: max-age=0',
//            'upgrade-insecure-requests: 1',
//            'user-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36',
//            'dnt: 1',
//            'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
//            'accept-language: en,ru;q=0.9,uk;q=0.8'
//        ]);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'cache-control: max-age=0',
            'upgrade-insecure-requests: 1',
            'user-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36',
            'dnt: 1',
            'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
//            'accept-encoding: gzip, deflate, br',
            'accept-language: en,ru;q=0.9,uk;q=0.8',
            'cookie: shippingCountry=UA; currency=UAH; SignedIn=0; GCs=CartItem1_92_03_87_UserName1_92_4_02_; _abck=4BB12C177CCDB0AC63956C4C446C311768605B4D995E00002921FE5B84698160~-1~x89WgEQywo1xWjobwet+sdFGm8uCXDP6ifase2rPzXM=~-1~-1; bm_sz=894678CEDA41BA1A08158D0B0BB786FA~QAAQTVtgaOM52TdnAQAAXIuxWMGhrQytr/9iyDsW+g/QZJJnSk/NMdRI1lZZJAUuBGPqCPBDiFE043f5Qa7SqirKDmlQkIZevul+nIzRBgM7EFkeZBQ/dgH4OcdFwWS6bBuQGJp5clsIPQidl2TUv8FVhtMx+V+UHn8btHTZO9YcHrfR3Dv3xCchOICdCA==; mercury=true; SEED=-6118623325069897240%7C%7C90-20; check=true; AMCVS_8D0867C25245AE650A490D4C%40AdobeOrg=1; FORWARDPAGE_KEY=https%3A%2F%2Fwww.macys.com%2Fshop%2Fwomens-clothing%2Fcyber-week-specials%3Fid%3D45110; cmTPSet=Y; AMCV_8D0867C25245AE650A490D4C%40AdobeOrg=-1891778711%7CMCIDTS%7C17864%7CMCMID%7C75707501051460875374611346078840157608%7CMCAAMLH-1543986095%7C6%7CMCAAMB-1543986095%7CRKhpRz8krg2tLO6pguXWp5olkAcUniQYPHaMWWgdJ3xzPWQmdj0y%7CMCOPTOUT-1543388495s%7CNONE%7CMCAID%7CNONE%7CvVersion%7C2.4.0; ak_bmsc=CE27F5BEDB1AF58BD45CB83EF42C600268605B4D995E00002D21FE5B260CA475~plNNNAfusP1qWr9qDfyOSrbwmYIqGAYMeI3iYhh6eY6rKYBaxNMCvm0+C66b0iBPO9DIvZLtFC8bQWpBu56kmWZpfoPnq9Sd5RhpLVOL8/MqLnbMxw0l8/lD1eOkCj4mrp1If3zMMEhdC+Z6lUoNC0rxHrIWCdAnQge3YE3HVZpffqPa6FluNj56R9t/QrH8RKLjPQxx9VlJ68G2lQ7/62V0M+uMNQqNgjRQz8A74t82ryr3+aLb0Ca5no+/AKjBWy; _ga=GA1.2.208725150.1543381298; _gid=GA1.2.1314696537.1543381298; _4c_mc_=41be7ca48e2f8f2bb4ce65e7baee891f; akavpau_www_www1_macys=1543388608~id=8c0ffc46163fbddec134262cbf61339a; mbox=PC#1bcc8c257b3246c7910f48f086f6fb32.26_30#1606626098|session#def4ff8317c549d2b81cc8a639076933#1543390170; utag_main=v_id:016758b1a12100796f5b5bb732d80306900470610086e$_sn:2$_ss:1$_st:1543390109446$vapi_domain:macys.com$ses_id:1543388309446%3Bexp-session$_pn:1%3Bexp-session; s_pers=%20c29%3Dmcom%253Awomen%253Acyber%2520week%2520specials%7C1543390109911%3B%20v30%3Dbrowse%7C1543390109912%3B; dca=WDC; TS01ad411f=011c4445918d1f674df90f5bca9b92607fe68ee8623bd61c91b514a8cda73052aa7df36c2a82c428ffe31a1f7e343c67e8ca28d730; TS0132ea28=011c444591742876b7fa3eae524c628da3d4befe59eb3f13562be2c00da816f5a4a9779d44; bm_sv=CB12B7B6EC10804A187552F1EC14246B~qU2jXEepc5fHlQ7uVMKm+Vctt8Ye+pwvveUdQ50BuT+gPHgNQslNwByOf2U9q3MNno2xfyKoxG7FxnuO2bM9PLWYRHmtekc8KcvojD1AcirKTVJqlNpXANn5j/ZP8l+NoO23EIyHkY9xQwKYTjZIzYUsHVjG0Pw+qeCtQMTdycs=; _gat_gtag_UA_63017854_1=1; smtrrmkr=636789851128600570%5Ef1b76f03-dbf2-e811-819c-c3b938c39251%5Ef2b76f03-dbf2-e811-819c-c3b938c39251%5E0%5E188.163.3.200; CRTOABE=0; s_sess=%20s_cc%3Dtrue%3B%20s_ppvl%3Dmcom%25253Awomen%25253Acyber%252520week%252520specials%252C6%252C6%252C639%252C1366%252C150%252C1366%252C768%252C1%252CP%3B%20s_ppv%3Dmcom%25253Awomen%25253Acyber%252520week%252520specials%252C1%252C1%252C150%252C1366%252C150%252C1366%252C768%252C1%252CP%3B; TLTSID=10095326354587774479612121110625; xdVisitorId=1119LSVBKnVCRnOweNT1hmr5p6GidXdh1epmMqZOusO_l8s51D5; atgRecVisitorId=1119LSVBKnVCRnOweNT1hmr5p6GidXdh1epmMqZOusO_l8s51D5; atgRecSessionId=7YVZHNx6nKnXX_sw0D62zN304j4J9gCZAqv5EVcBk7qNKyiCCwTq!1050129626!-1403431258; RT="sl=1&ss=1543388304650&tt=12207&obo=0&sh=1543388316902%3D1%3A0%3A12207&dm=macys.com&si=22ff4782-2497-43ed-ba50-74a603fa3aa4&bcn=%2F%2F22ff71a1.akstat.io%2F&ld=1543388316902&r=https%3A%2F%2Fwww.macys.com%2Fshop%2Fwomens-clothing%2Fcyber-week-specials%3Fid%3D45110&ul=1543388329458"',
            'if-none-match: W/"1b1d9e-14qRKZ0pkqXsMytqs0qGlYAAKzM"'
        ]);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    /**
     * @param $link
     *
     * @return HtmlParser
     */
    public static function factoryByLink($link)
    {
        if (isset(self::$instances[$link])) {
            return self::$instances[$link];
        }

        self::$instances[$link] = new static($link, true);

        return self::$instances[$link];
    }

    /**
     * @param $html
     *
     * @return HtmlParser
     */
    public static function factoryByHtml($html)
    {
        return new static($html, false);
    }

    public function query($query)
    {
        $nodes = [];

        foreach (explode(',', $query) as $query2) {
            $query2 = trim($query2);

            foreach ($this->adapter->select($query2) as $node) {
                $nodes[] = $node;
            }
        }

        return new pQuery($nodes);
    }
}