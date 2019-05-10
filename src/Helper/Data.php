<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 2/2/17
 * Time: 10:39 PM
 */

namespace SNOWGIRL_CORE\Helper;

/**
 * Class Data
 * @package SNOWGIRL_CORE\Helper
 */
class Data
{
    public static function isRuText($text)
    {
        return preg_match('/[А-Яа-яЁё]/u', $text);
    }

    public static function isEnText($text)
    {
        return preg_match('/[A-Za-z]/', $text);
    }

    /**
     * htmlspecialchars including multi-byte characters
     *
     * @access public static
     * @param  string $value Input String
     * @return string Parsed string
     */
    public static function toSafeHtml($value = '')
    {
        if ($value) {
            // Use forward look up to only convert & not &#123;
            $value = preg_replace('/&(?!#[0-9]+;)/s', '&amp;', $value);
            $value = str_replace(['<', '>', '"', "'", "`"], ['&lt;', '&gt;', '&quot;', '&#039;', "&#96;"], $value);
            $value = str_replace("%3C%73%63%72%69%70%74", "&lt;script", $value);
            $value = str_replace("%64%6F%63%75%6D%65%6E%74%2E%63%6F%6F%6B%69%65", "document&#46;cookie", $value);
            $value = preg_replace("#javascript\:#is", "java script:", $value);
            $value = preg_replace("#vb(.+?)?script\:#is", "vb script:", $value);
            $value = preg_replace("#moz\-binding:#is", "moz binding:", $value);
        }

        return $value;
    }

    /**
     * unhtmlspecialchars including multi-byte characters
     *
     * @access public static
     * @param  string $value Input String
     * @return string Parsed string
     */
    public static function toUnSafeHtml($value = "")
    {
        if ($value) {
            $value = str_replace("&amp;", "&", $value);
            $value = str_replace(['&lt;', '&gt;', '&quot;', '&#039;', "&#96;"], ['<', '>', '"', "'", "`"], $value);
        }

        return $value;
    }

    /**
     * Function switch keyboard layout to required lang
     *
     * @param $value
     * @param string $lang
     * @return mixed
     */
    public static function keyboardSwitchTo($value, $lang = 'ru')
    {
        $switch['ru'] = [
            'from' => ['q', 'w', 'e', 'r', 't', 'y', 'u', 'i', 'o', 'p', '[', ']', '{', '}', 'a', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'l', ';', ':', '\'', '"', 'z', 'x', 'c', 'v', 'b', 'n', 'm', ',', '.', '<', '>', '`', '~'],
            'to' => ['й', 'ц', 'у', 'к', 'е', 'н', 'г', 'ш', 'щ', 'з', 'х', 'ъ', 'х', 'ъ', 'ф', 'ы', 'в', 'а', 'п', 'р', 'о', 'л', 'д', 'ж', 'ж', 'э', 'э', 'я', 'ч', 'с', 'м', 'и', 'т', 'ь', 'б', 'ю', 'б', 'ю', 'ё', 'ё'],
        ];

        $switch['ua'] = [
            'from' => ['q', 'w', 'e', 'r', 't', 'y', 'u', 'i', 'o', 'p', '[', ']', '{', '}', 'a', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'l', ';', ':', '\'', '"', 'z', 'x', 'c', 'v', 'b', 'n', 'm', ',', '.', '<', '>'],
            'to' => ['й', 'ц', 'у', 'к', 'е', 'н', 'г', 'ш', 'щ', 'з', 'х', 'ї', 'х', 'ї', 'ф', 'і', 'в', 'а', 'п', 'р', 'о', 'л', 'д', 'ж', 'ж', 'є', 'є', 'я', 'ч', 'с', 'м', 'и', 'т', 'ь', 'б', 'ю', 'б', 'ю'],
        ];

        if (isset($switch[$lang])) {
            $value = str_replace($switch[$lang]['from'], $switch[$lang]['to'], $value);
        }

        return $value;
    }

    /**
     * Function converts $value from Translit to $lang
     *
     * @param $value
     * @param string $lang
     * @return string
     */
    public static function fromTranslit($value, $lang = 'ru')
    {
        $reverseTransliterate['ru'] = [
            'a' => 'а', 'b' => 'б', 'v' => 'в',
            'g' => 'г', 'd' => 'д', 'e' => 'е',
            'yo' => 'ё', 'zh' => 'ж', 'z' => 'з',
            'i' => 'и', 'j' => 'й', 'k' => 'к',
            'l' => 'л', 'm' => 'м', 'n' => 'н',
            'o' => 'о', 'p' => 'п', 'r' => 'р',
            's' => 'с', 't' => 'т', 'u' => 'у',
            'f' => 'ф', 'kh' => 'х', 'c' => 'ц',
            'ch' => 'ч', 'sh' => 'ш', 'shch' => 'щ',
            '\'' => 'ь', 'y' => 'ы',
            'e\'' => 'э', 'yu' => 'ю', 'ya' => 'я',
            'h' => 'х',

            'A' => 'А', 'B' => 'Б', 'V' => 'В',
            'G' => 'Г', 'D' => 'Д', 'E' => 'Е',
            'Yo' => 'Ё', 'Zh' => 'Ж', 'Z' => 'З',
            'I' => 'И', 'J' => 'Й', 'K' => 'К',
            'L' => 'Л', 'M' => 'М', 'N' => 'Н',
            'O' => 'О', 'P' => 'П', 'R' => 'Р',
            'S' => 'С', 'T' => 'Т', 'U' => 'У',
            'F' => 'Ф', 'Kh' => 'Х', 'C' => 'Ц',
            'Ch' => 'Ч', 'Sh' => 'Ш', 'Shch' => 'Щ',
            '\'' => 'Ь', 'Y' => 'Ы',
            'E\'' => 'Э', 'Yu' => 'Ю', 'Ya' => 'Я',
            'H' => 'Х',
        ];

        $reverseTransliterate['ua'] = [
            "A" => "А", "B" => "Б", "V" => "В", "H" => "Г", "G" => "Ґ", "D" => "Д",
            "E" => "Е", "Ye" => "Є", "Zh" => "Ж", "Z" => "З", "Y" => "И", "I" => "І",
            "Yi" => "Ї", "Y" => "Й", "K" => "К", "L" => "Л", "M" => "М", "N" => "Н",
            "O" => "О", "P" => "П", "R" => "Р", "S" => "С", "T" => "Т", "U" => "У",
            "F" => "Ф", "Kh" => "Х", "Ts" => "Ц", "Ch" => "Ч", "Sh" => "Ш", "Shch" => "Щ",
            "Yu" => "Ю", "Ya" => "Я",
            "a" => "а", "b" => "б", "v" => "в", "h" => "г", "g" => "ґ", "d" => "д",
            "e" => "е", "ye" => "є", "ie" => "є", "zh" => "ж", "z" => "з", "y" => "и", "i" => "і",
            "yi" => "ї", "k" => "к", "l" => "л", "m" => "м", "n" => "н",
            "o" => "о", "p" => "п", "r" => "р", "s" => "с", "t" => "т", "u" => "у",
            "f" => "ф", "kh" => "х", "ts" => "ц", "ch" => "ч", "sh" => "ш", "shch" => "щ",
            "yu" => "ю", "iu" => "ю", "ya" => "я", "ia" => "я",
        ];

        if (isset($reverseTransliterate[$lang])) {
            $value = strtr($value, $reverseTransliterate[$lang]);
        }

        return $value;
    }

    /**
     * Function converts $value from Translit to $lang
     *
     * @param $value
     * @param string $lang
     * @return string
     */
    public static function toTransliterateSearch($value, $lang = 'ru')
    {
        $transliterate['ru'] = [
            'а' => 'a', 'б' => 'b', 'в' => 'v',
            'г' => 'g', 'д' => 'd', 'е' => 'e',
            'ё' => 'yo', 'ж' => 'zh', 'з' => 'z',
            'и' => 'i', 'й' => 'j', 'к' => 'k',
            'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r',
            'с' => 's', 'т' => 't', 'у' => 'u',
            'ф' => 'f', 'х' => 'kh', 'ц' => 'c',
            'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch',
            'ь' => '\'', 'ы' => 'y',
            'э' => 'e\'', 'ю' => 'yu', 'я' => 'ya',

            'А' => 'A', 'Б' => 'B', 'В' => 'V',
            'Г' => 'G', 'Д' => 'D', 'Е' => 'E',
            'Ё' => 'Yo', 'Ж' => 'Zh', 'З' => 'Z',
            'И' => 'I', 'Й' => 'J', 'К' => 'K',
            'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R',
            'С' => 'S', 'Т' => 'T', 'У' => 'U',
            'Ф' => 'F', 'Х' => 'Kh', 'Ц' => 'C',
            'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Shch',
            'Ь' => '\'', 'Ы' => 'Y',
            'Э' => 'E\'', 'Ю' => 'Yu', 'Я' => 'Ya',
        ];

        $transliterate['ua'] = [
            "А" => "A", "Б" => "B", "В" => "V", "Г" => "H", "Ґ" => "G", "Д" => "D",
            "Е" => "E", "Є" => "Ye", "Ж" => "Zh", "З" => "Z", "И" => "Y", "І" => "I",
            "Ї" => "Yi", "Й" => "Y", "К" => "K", "Л" => "L", "М" => "M", "Н" => "N",
            "О" => "O", "П" => "P", "Р" => "R", "С" => "S", "Т" => "T", "У" => "U",
            "Ф" => "F", "Х" => "Kh", "Ц" => "Ts", "Ч" => "Ch", "Ш" => "Sh", "Щ" => "Shch",
            "Ю" => "Yu", "Я" => "Ya",
            "а" => "a", "б" => "b", "в" => "v", "г" => "h", "ґ" => "g", "д" => "d",
            "е" => "e", "є" => "ye", "є" => "ie", "ж" => "zh", "з" => "z", "и" => "y", "і" => "i",
            "ї" => "yi", "к" => "k", "л" => "l", "м" => "m", "н" => "n",
            "о" => "o", "п" => "p", "р" => "r", "с" => "s", "т" => "t", "у" => "u",
            "ф" => "f", "х" => "kh", "ц" => "ts", "ч" => "ch", "ш" => "sh", "щ" => "shch",
            "ю" => "yu", "ю" => "iu", "я" => "ya", "я" => "ia",
        ];

        if (isset($transliterate[$lang])) {
            $value = strtr($value, $transliterate[$lang]);
        }

        return $value;
    }

    public static function normalizeText($string)
    {
        $string = strtr($string, [
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ą' => 'a', 'ā' => 'a',
            'Đ' => 'D',
            'đ' => 'd',
            'È' => 'E', 'É' => 'E',
            'è' => 'e', 'é' => 'e', 'ę' => 'e',
            'Ç' => 'C', 'Č' => 'C', 'Ć' => 'C',
            'ç' => 'c', 'č' => 'c', 'ć' => 'c',
            'Ê' => 'E', 'Ë' => 'Е', 'Ё' => 'Е',
            'ê' => 'e', 'ë' => 'е', 'ё' => 'е',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ð' => 'o',
            'Ñ' => 'N',
            'ñ' => 'n', 'ń' => 'n',
            'Ł' => 'L',
            'ł' => 'l',
            'Ŕ' => 'R',
            'ŕ' => 'r',
            'Š' => 'S', 'Ś' => 'S',
            'š' => 's', 'ś' => 's', 'ß' => 's',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'Ý' => 'Y',
            'ý' => 'y', 'ÿ' => 'y',
            'Þ' => 'B',
            'þ' => 'b',
            'Ž' => 'Z', 'Ź' => 'Z', 'Ż' => 'Z',
            'ž' => 'z', 'ź' => 'z', 'ż' => 'z',
        ]);

        $string = preg_replace("/[^0-9a-zA-Zа-яА-Я\%\$\!\?\#\-&\/,\(\)\.\:]/u", ' ', $string);
        $string = preg_replace('/\s+/', ' ', $string);
        $string = trim($string);

        return $string;
    }

    /**
     * Function converts string to URI valid string
     *
     * @param $string
     * @return mixed|string
     */
    public static function normalizeUri($string)
    {
        $string = trim(urldecode($string));
        $string = self::toUrlUtf($string);
        return $string;
    }

    /**
     * @param $string
     * @return mixed|string
     */
    public static function normalizeUri2($string)
    {
        $string = self::toUrlUtf($string);
        $string = iconv('UTF-8', 'UTF-8//IGNORE', strtr($string, [
            'а' => 'a', 'А' => 'a',
            'б' => 'b', 'Б' => 'b',
            'в' => 'v', 'В' => 'v',
            'г' => 'g', 'Г' => 'g',
            'д' => 'd', 'Д' => 'd',
            'е' => 'e', 'Е' => 'e',
            'ё' => 'e', 'Ё' => 'e',
            'ж' => 'zh', 'Ж' => 'zh',
            'з' => 'z', 'З' => 'z',
            'и' => 'i', 'И' => 'i',
            'й' => 'y', 'Й' => 'y',
            'к' => 'k', 'К' => 'k',
            'л' => 'l', 'Л' => 'l',
            'м' => 'm', 'М' => 'm',
            'н' => 'n', 'Н' => 'n',
            'о' => 'o', 'О' => 'o',
            'п' => 'p', 'П' => 'p',
            'р' => 'r', 'Р' => 'r',
            'с' => 's', 'С' => 's',
            'т' => 't', 'Т' => 't',
            'у' => 'u', 'У' => 'u',
            'ф' => 'f', 'Ф' => 'f',
            'х' => 'h', 'Х' => 'h',
            'ц' => 'c', 'Ц' => 'c',
            'ч' => 'ch', 'Ч' => 'ch',
            'ш' => 'sh', 'Ш' => 'sh',
            'щ' => 'sch', 'Щ' => 'sch',
            'ъ' => '', 'Ъ' => '',
            'ы' => 'y', 'Ы' => 'y',
            'ь' => '', 'Ь' => '',
            'э' => 'e', 'Э' => 'e',
            'ю' => 'yu', 'Ю' => 'yu',
            'я' => 'ya', 'Я' => 'ya',
            'і' => 'i', 'І' => 'i',
            'ї' => 'yi', 'Ї' => 'yi',
            'є' => 'e', 'Є' => 'e'
        ]));

        $string = htmlentities($string, ENT_QUOTES, 'UTF-8');

        $pattern = '~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i';
        $string = preg_replace($pattern, '$1', $string);

        $string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');

        $pattern = '~[^0-9a-z]+~i';
        $string = preg_replace($pattern, '-', $string);

        $string = strtolower(trim($string, '-'));

        return $string;
    }

    /**
     * Function converts CYR to Translit (filesystem suitable)
     *
     * @param  string $value
     * @return string
     */
    public static function transliterateRuToEn($value, $toFS = true)
    {
        $conv1 = ["А", "Б", "В", "Г", "Д", "Е", "Ё", "Ж", "З", "И", "Й", "К", "Л", "М", "Н", "О", "П", "Р", "С", "Т", "У", "Ф", "Х", "Ц", "Ч", "Ш", "Щ", "Ъ", "Ы", "Ь", "Э", "Ю", "Я", "Є", "Ї", "І", "Ґ",
            "а", "б", "в", "г", "д", "е", "ё", "ж", "з", "и", "й", "к", "л", "м", "н", "о", "п", "р", "с", "т", "у", "ф", "х", "ц", "ч", "ш", "щ", "ъ", "ы", "ь", "э", "ю", "я", "є", "ї", "і", "ґ"];
        $conv2 = ["A", "B", "V", "G", "D", "E", "Jo", "Zh", "Z", "I", "J", "K", "L", "M", "N", "O", "P", "R", "S", "T", "U", "F", "H", "C", "Ch", "Sh", "Shch", "", "Y", "", "Je", "Ju", "Ja", "E", "I", "I", "G",
            "a", "b", "v", "g", "d", "e", "jo", "zh", "z", "i", "j", "k", "l", "m", "n", "o", "p", "r", "s", "t", "u", "f", "h", "c", "ch", "sh", "shch", "", "y", "", "je", "ju", "ja", "e", "i", "i", "g"];

        $value = str_replace($conv1, $conv2, $value);

        if ($toFS) {
            $value = preg_replace("/[^a-zA-Z0-9_\-]/", " ", $value);
            $value = preg_replace("/\s+/", "_", $value);
            $value = trim($value, "_");
        }

        return $value;
    }

    /**
     * Translit by the rule УКППТ 1996
     * @param string $name
     * @return string
     */
    public static function transliterateUaToEn($name)
    {
        $UaToEn = ["А" => "A", "Б" => "B", "В" => "V", "Г" => "H", "Ґ" => "G", "Д" => "D", "Е" => "E", "Є" => "Ye", "Ж" => "Zh", "З" => "Z", "И" => "Y", "І" => "I", "Ї" => "Yi", "Й" => "Y", "К" => "K", "Л" => "L", "М" => "M", "Н" => "N", "О" => "O", "П" => "P", "Р" => "R", "С" => "S", "Т" => "T", "У" => "U", "Ф" => "F", "Х" => "Kh", "Ц" => "Ts", "Ч" => "Ch", "Ш" => "Sh", "Щ" => "Shch", "Ю" => "Yu", "Я" => "Ya",
            "а" => "a", "б" => "b", "в" => "v", "г" => "h", "ґ" => "g", "д" => "d", "е" => "e", "є" => ["ye", "ie"], "ж" => "zh", "з" => "z", "и" => "y", "і" => "i", "ї" => ["yi", "i"], "й" => ["y", "i"], "к" => "k", "л" => "l", "м" => "m", "н" => "n", "о" => "o", "п" => "p", "р" => "r", "с" => "s", "т" => "t", "у" => "u", "ф" => "f", "х" => "kh", "ц" => "ts", "ч" => "ch", "ш" => "sh", "щ" => "shch", "ю" => ["yu", "iu"], "я" => ["ya", "ia"]];
        $newName = '';
        $chars = preg_split('%%u', $name);
        $countChar = count($chars);
        for ($i = 0; $i < $countChar; $i++) {
            if (!array_key_exists($chars[$i], $UaToEn)) {
                if ($chars[$i] != "'" && $chars[$i] != "ь") {
                    $newName .= $chars[$i];
                }
            } elseif (is_array($UaToEn[$chars[$i]])) {
                $newName .= (!isset($chars[$i - 1]) || $chars[$i - 1] == '-' || $chars[$i - 1] == ' ') ? $UaToEn[$chars[$i]][0] : $UaToEn[$chars[$i]][1];
            } else {
                if (($chars[$i] == 'З' || $chars[$i] == 'з') && (isset($chars[$i + 1]) && $chars[$i + 1] == 'г')) {
                    $newName .= $chars[$i] == 'З' ? 'Zgh' : 'zgh';
                    $i++;
                } else {
                    $newName .= $UaToEn[$chars[$i]];
                }
            }
        }
        return $newName;
    }

    public static function toUrlRu($value, $toFS = true)
    {
        $value = mb_strtolower($value);
        $value = preg_replace("/[^ҐґЄєІіЇїёа-яА-Яa-zA-Z0-9_\-]/ui", " ", $value);
        $value = preg_replace("/\s+/", "-", $value);
        $value = trim($value, "-");

        return $value;
    }

    public static function toUrlUtf($value)
    {
        $value = mb_strtolower($value);
        $value = preg_replace("/[!@#$%^&*()|?.,<>;:{}=+~`]/ui", " ", $value);
        $value = preg_replace("/\s+/", "-", $value);
        $value = trim($value, "-");

        return $value;
    }

    /**
     * Возвращает сообщение в соответствующей числу форме.
     *
     * @param  int $n Число
     * @param  string $msgid1 Наименование для 1, 21, 31, 41, ...
     * @param  string $msgid2 Наименование для 2..4, 22..24, 32..34, ...
     * @param  string $msgid3 Наименование для 5..20, 25..30, 35..40, ...
     * @return string
     */
    public static function toPlural($n, $msg1, $msg2, $msg3 = '')
    {
        $plural = ($n % 10 == 1 && $n % 100 != 11 ? 0 : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? 1 : 2));
        return sprintf($plural == 0 ? $msg1 : ($plural == 1 ? $msg2 : ($msg3 ? $msg3 : $msg2)), $n);
    }

    /**
     * Преобразовывает кол-во байт в человеко понятную величину
     *
     * @param  int $value размер в байтах
     * @param  bool $split обьединять с названием или возвращать раздельно в массиве
     * @return mixed
     */
    public static function toHumanBytes($value = 0, $split = true)
    {
        $q = $name = null;

        foreach ([
                     '1208925819614629174706176' => 'Yb',
                     '1180591620717411303424' => 'Zb',
                     '1152921504606846976' => 'Eb',
                     '1125899906842624' => 'Pb',
                     '1099511627776' => 'Tb',
                     '1073741824' => 'Gb',
                     '1048576' => 'Mb',
                     '1024' => 'Kb',
                     '0' => 'B'
                 ] as $q => $name) {
            if ($value >= (float)$q) {
                break;
            }
        }

        $value = strval($q ? round($value / $q * 100) / 100 : $value);

        return $split ? $value . ' ' . $name : ['name' => $name, 'value' => $value];
    }

    /**
     * Get Unix timestamp for a date in different formats
     *
     * @access public static
     * @param string $date datetime value
     * @param string $format_from input datetime format
     * @return int                  Unix timestamp
     */
    public static function toMkTime($date, $format_from = 'Y-m-d H:i:s')
    {
        //list ($month, $day, $year) = explode('-',date('m-d-Y'));
        $month = $day = $year = 0;
        $hour = $minute = $second = 0;
        static $monthbyname = ['F' => [], 'M' => []];
        for ($i = 1; $i < 13; $i++) {
            $monthbyname['F'][date('F', mktime(12, 0, 0, $i, 1))] = $i;
            $monthbyname['M'][date('M', mktime(12, 0, 0, $i, 1))] = $i;
        }

        $regs = [
            'a' => '(am|pm)',
            'A' => '(AM|PM)',
            'd' => '(0[1-9]|[12]\d|3[01])',
            'F' => '(' . implode('|', array_keys($monthbyname['F'])) . ')',
            'g' => '([1-9]|1[0-2])',
            'G' => '(\d|1\d|2[0-3])',
            'h' => '(0[1-9]|1[0-2])',
            'H' => '([01]\d|2[0-3])',
            'i' => '([0-5]\d)',
            'j' => '([1-9]|[12]\d|3[01])',
            'm' => '(0[1-9]|1[0-2])',
            'M' => '(' . implode('|', array_keys($monthbyname['M'])) . ')',
            'n' => '([1-9]|1[0-2])',
            's' => '([0-5]\d)',
            'y' => '(\d{2})',
            'Y' => '((?:19|20)\d{2})'
        ];

        $parts = preg_split('/(?<!\x5c)([aAdFgGhHijmMnsyY])/', $format_from, -1, PREG_SPLIT_DELIM_CAPTURE);

        $dateparts = [];

        foreach ($parts as $k => $part) {
            if (isset($regs[$part])) {
                $dateparts[] = $part;
                $parts[$k] = $regs[$part];
            } else {
                $parts[$k] = preg_quote(stripslashes($part), '!');
            }
        }
        $real_reg = '!' . implode('', $parts) . '!';

        preg_match_all($real_reg, $date, $founds);

        foreach ($dateparts as $k => $v) {
            if (isset($founds[$k + 1][0])) {
                $founds[$v] = $founds[$k + 1][0];
            }
        }

        if (isset($founds['h'])) {
            $hour = $founds['h'];
        } elseif (isset($founds['H'])) {
            $hour = $founds['H'];
        } elseif (isset($founds['g'])) {
            $hour = $founds['g'];
        } elseif (isset($founds['G'])) {
            $hour = $founds['G'];
        }

        if ((isset($founds['a']) && 'pm' == $founds['a']) || (isset($founds['A']) && 'PM' == $founds['A'])) {
            $hour += 12;
        }

        if (isset($founds['i'])) {
            $minute = $founds['i'];
        }

        if (isset($founds['s'])) {
            $second = $founds['s'];
        }

        if (isset($founds['F'])) {
            $month = $monthbyname['F'][$founds['F']];
        } elseif (isset($founds['M'])) {
            $month = $monthbyname['M'][$founds['M']];
        } elseif (isset($founds['m'])) {
            $month = $founds['m'];
        } elseif (isset($founds['n'])) {
            $month = $founds['n'];
        }

        if (isset($founds['d'])) {
            $day = $founds['d'];
        } elseif (isset($founds['j'])) {
            $day = $founds['j'];
        }

        if (isset($founds['Y'])) {
            $year = $founds['Y'];
        } elseif (isset($founds['y'])) {
            $year = $founds['y'];
        }

        $_ts = mktime($hour, $minute, $second, $month, $day, $year);

        return ($month + $day + $year + $hour + $minute + $second == 0) || $_ts < 0 ? 0 : $_ts;
    }

    /**
     * Функция предназначена для сортировки массива по нескольким параметрам
     * Эдакий эквивалент SQLного SORT BY бла-бла-бла
     *
     * @param array $data сортируемый массив
     * @param array $orders поля, по которым сортируем в формате [field]=>direction, где
     *                       field - имя поля, direction - направление (TRUE=>ASC, FALSE=>DESC)
     * @return array         отсортированный массив
     */
    public static function orderHash(array $data = null, array $orders = null)
    {
        if (!is_null($orders) && !is_null($data)) {
            $code = '';
            foreach ($orders as $field => $direction) {
                $code .= 'if ($a["' . $field . '"] != $b["' . $field . '"]) {';
                if ($direction) {
                    $code .= 'return ($a["' . $field . '"] < $b["' . $field . '"] ? -1 : 1); }';
                } else {
                    $code .= 'return ($a["' . $field . '"] < $b["' . $field . '"] ? 1 : -1); }';
                }
            }
            $code .= 'return 0;';
            $compare = create_function('$a,$b', $code);
            usort($data, $compare);
        }
        return $data;
    }

    /**
     * Считает crc32 как знаковый интеджер с поправкой на 64-битную систему
     *
     * @param  mixed $txt значение
     * @return int
     */
    public static function toCrc32($txt)
    {
        $crc = crc32($txt);

        if ($crc & 0x80000000) {
            $crc ^= 0xffffffff;
            $crc += 1;
            $crc = -$crc;
        }

        return $crc;
    }

    public static function ucFirst($str, $enc = 'utf-8')
    {
        return mb_strtoupper(mb_substr($str, 0, 1, $enc), $enc) . mb_substr($str, 1, mb_strlen($str, $enc), $enc);
    }

    public static function ucWords($text)
    {
        return mb_convert_case($text, MB_CASE_TITLE, "UTF-8");
    }
}