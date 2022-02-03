<?php
class Numgle
{
    public $input;
    public $dataset;
    function __construct()
    {
        //echo $_SERVER["DOCUMENT_ROOT"];
        //왜인지 몰?루겠는데 $_SERVER["DOCUMENT_ROOT"]; 실행 시 public 폴더로 가 있어서, 상대경로 다시 설정.
        $jsonParser = file_get_contents("../app/dataset/data.json");
        $this->dataset = json_decode($jsonParser, true);
        //var_dump($this->dataset);
    }


    function convertStringToNumgle($input)
    {

        /*$str = "가";
        echo mb_ord($str);*/
        if (mb_strlen($input) == 0) {
            return "";
        }
        $arr = array();
        for ($i = 0; $i < mb_strlen($input); $i++) {
            //echo $i;
            /*echo mb_substr($input, $i, 1, "utf-8");
            echo "\n";*/
            //echo mb_ord(mb_substr($input, $i, 1))."<br>";
            array_push($arr, $this->convertCharToNumgle(mb_substr($input, $i, 1)));
        }
        $output = join("<br>", $arr);

        return $output;
    }
    function convertCharToNumgle($input)
    {
        //$i = $this->utf8_char_code_at($input, 0); //유니코드 출력
        $i = $this->charCodeAt($input);

        $letterType = $this->getLetterType($i);
        //echo $letterType;

        $start = 0;
        $result = "";

        switch ($letterType) {
            case LetterType::empty: //1
                $result = "";
                break;

            case LetterType::completeHangul:
                //$result = mb_chr($this->completeHangul($i));
                $result = $this->completeHangul($i);
                break;

            case LetterType::notCompleteHangul:
                $start = $this->dataset["range"]["notCompleteHangul"]["start"];
                $result = $this->dataset["englishUpper"][$i - $start];
                break;

            case LetterType::englishUpper:
                $start = $this->dataset["range"]["uppercase"]["start"];
                $result = $this->dataset["englishUpper"][$i - $start];
                break;

            case LetterType::englishLower:
                $start = $this->dataset["range"]["lowercase"]["start"];
                $result = $this->dataset["englishLower"][$i - $start];
                break;

            case letterType::number:
                $start = $this->dataset["range"]["number"]["start"];
                $result = $this->dataset["number"][$i - $start];
                break;

            case LetterType::specialLetter:
                $result = $this->dataset["special"][strpos($this->dataset["range"]["special"], $i)];
                break;

            case LetterType::unkown:
                break;

            default:
                echo "There is a letter not converted";
        }

        return $result;
    }

    function completeHangul($input)
    {
        $separatedHan = $this->separateHan($input);
        //var_dump($separatedHan);
        
        if (!$this->isInData($separatedHan["cho"], $separatedHan["jung"], $separatedHan["jong"])) {
            echo "There is a letter not converted";
            return "";
        }
        if ($separatedHan["jung"] >= 8 && $separatedHan["jung"] != 20) {

            //echo mb_chr($this->charCodeAt($this->dataset["jong"][$separatedHan["jong"]])).mb_chr($this->charCodeAt($this->dataset["jung"][$separatedHan["jung"] - 8])).mb_chr($this->charCodeAt($this->dataset["cho"][$separatedHan["cho"]]));
            //return $this->charCodeAt($this->dataset["jong"][$separatedHan["jong"]]) + $this->charCodeAt($this->dataset["jung"][$separatedHan["jung"] - 8]) +  $this->charCodeAt($this->dataset["cho"][$separatedHan["cho"]]);
            return mb_chr($this->charCodeAt($this->dataset["jong"][$separatedHan["jong"]])).mb_chr($this->charCodeAt($this->dataset["jung"][$separatedHan["jung"] - 8])).mb_chr($this->charCodeAt($this->dataset["cho"][$separatedHan["cho"]]));
        }
        //echo $this->charat($this->dataset["jong"][$separatedHan["jong"]])[1];
        //echo $this->dataset["jong"][$separatedHan["jong"]];
        //echo mb_chr($this->charCodeAt($this->dataset["jong"][$separatedHan["jong"]])).mb_chr($this->charCodeAt($this->dataset["cj"][min(8, $separatedHan["jung"])][$separatedHan["cho"]]));
        //return $this->charCodeAt($this->dataset["jong"][$separatedHan["jong"]]) + $this->charCodeAt($this->dataset["cj"][min(8, $separatedHan["jung"])][$separatedHan["cho"]]);
        return mb_chr($this->charCodeAt($this->dataset["jong"][$separatedHan["jong"]])).mb_chr($this->charCodeAt($this->dataset["cj"][min(8, $separatedHan["jung"])][$separatedHan["cho"]]));
    }

    //유니코드 때문에 따로 만듬
    //https://stackoverflow.com/questions/10333098/utf-8-safe-equivalent-of-ord-or-charcodeat-in-php
    function utf8_char_code_at($str, $index)
    {
        $char = mb_substr($str, $index, 1, 'UTF-8');

        if (mb_check_encoding($char, 'UTF-8')) {
            $ret = mb_convert_encoding($char, 'UTF-32BE', 'UTF-8');
            return hexdec(bin2hex($ret));
        } else {
            return null;
        }
    }

    //직접만듬
    function charCodeAt($str) {
        if($str == "") {
            return 0;
        } else {
            return mb_ord($str);
        }
    }

    function separateHan($han)
    {
        //echo $han."<br>";
        $hanStart = $this->dataset["range"]["completeHangul"]["start"]; //44032
        
        $obj = array(
            "cho" => floor(($han - $hanStart) / 28 / 21),
            "jung" => floor(($han - $hanStart) / 28 % 21),
            "jong" => floor(($han - $hanStart) % 28)
        );
        /*echo "<br>".mb_chr($han);
        echo "<br>";
        var_dump($obj);
        echo "<br>";
        */

        return $obj;
    }

    function isInData($cho_num, $jung_num, $jong_num)
    {
        if ($jong_num != 0 && $this->dataset["jong"][$jong_num] == '') return false;
        if ($jung_num >= 8 && $jung_num != 20) return $this->dataset["jung"][$jung_num - 8] != '';
        else return $this->dataset["cj"][min(8, $jung_num)] != '';
    }

    function getLetterType($code)
    {
        $dr = $this->dataset["range"];
        //echo $dr["completeHangul"]["start"];
        //echo ord($code);
        if ($code == '' || $code == '\r' || $code == '\n') return LetterType::empty;
        else if ($code >= $dr["completeHangul"]["start"] && $code <= $dr["completeHangul"]["end"]) return LetterType::completeHangul;
        else if ($code >= $dr["notCompleteHangul"]["start"] && $code <= $dr["notCompleteHangul"]["end"]) return LetterType::notCompleteHangul;
        else if ($code >= $dr["uppercase"]["start"] && $code <= $dr["uppercase"]["end"]) return LetterType::englishUpper;
        else if ($code >= $dr["lowercase"]["start"] && $code <= $dr["lowercase"]["end"]) return LetterType::englishLower;
        else if ($code >= $dr["number"]["start"] && $code <= $dr["number"]["end"]) return LetterType::number;
        else if (array_search($code, $dr["special"])) return LetterType::specialLetter;
        else return LetterType::unkown;
    }
}
//php 8.1부터 있기 때문에, 현재 개발 환경(php 8.0.8)에는 없음.
/*enum LetterType {
    case 
}*/

//enum 대신 사용
abstract class LetterType
{
    const empty = 1;
    const completeHangul = 2;
    const notCompleteHangul = 3;
    const englishUpper = 4;
    const englishLower = 5;
    const number = 6;
    const specialLetter = 7;
    const unkown = 8;
}
