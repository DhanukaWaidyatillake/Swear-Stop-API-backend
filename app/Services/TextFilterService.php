<?php

namespace App\Services;

use App\Models\BlacklistedWord;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class TextFilterService
{
    public function filterText($user_id, $text): array
    {
        $black_listed_words = BlacklistedWord::query()->where('user_id', $user_id)->where('is_enabled',true)->get()->pluck('word')->toArray();

        $sentence = $text;
        $words = explode(" ", $sentence);
        $refined_sentence = "";

        $banned_words_in_sentence = [];

        //Determining delimiter
        $all_banned_words_string = implode($black_listed_words);
        $all_delimiters = ["/", "#", "~", "!", "|", "@", "%", "&", "^", "*"];
        $charactersNotInString = array_diff($all_delimiters, str_split($all_banned_words_string));
        $delimiter = $charactersNotInString[0];

        //Checking for banned words before refining sentence
        $banned_words_in_sentence[] = $this->checkingForBannedWordsInGivenSentence($black_listed_words, $delimiter, $sentence, $words);

        //Refining sentence
        foreach ($words as $word) {
            if (!ctype_digit($word)) {
                $letter_combinations = [
                    'a' => ['α', '4', 'ⓐ', '⒜', 'ᾰ', 'ḁ', 'ἀ', 'ἁ', 'ἂ', 'ἃ', 'ἄ', 'ἅ', 'ἆ', 'ἇ', 'ạ', 'ả', 'ầ', 'ấ', 'ẩ', 'ẫ', 'ậ', 'ắ', 'ằ', 'ẳ', 'ẵ', 'ặ', 'ẚ', 'ᾱ', 'ᾲ', 'ᾳ', 'ᾴ', 'ᾶ', 'ᾷ', 'a', 'Ѧ', 'Ặ', 'Ἀ', 'Ἁ', 'Ἂ', 'Ἃ', 'Ἄ', 'Ἅ', 'Ἆ', 'Ἇ', 'Ạ', 'Ả', 'Ấ', 'Ầ', 'Ẩ', 'Ẫ', 'Ậ', 'Ắ', 'Ằ', 'Ẳ', 'Ẵ', 'A'],
                    'b' => ['⒝', 'ദ', '൫', '♭', 'ḃ', 'ḅ', 'ḇ', 'b', 'ℬ', 'Ḃ', 'Ḅ', 'Ḇ', 'B'],
                    'c' => ['ⓒ', '⒞', 'ḉ', 'c', 'ℂ', 'ℭ', '℃', '₡', '∁', 'C'],
                    'd' => ['ⓓ', '⒟', 'ⅾ', 'ḋ', 'ḍ', 'ḏ', 'ḑ', 'ḓ', 'd', 'Ḓ', 'Ḋ', 'Ḍ', 'Ḏ', 'Ḑ', 'D'],
                    'e' => ['🄴', '3', 'ⓔ', '⒠', 'ℯ', '∊', '€', 'ḕ', 'ḗ', 'ḙ', 'ḛ', 'ḝ', 'ẹ', 'ẻ', 'ẽ', 'ế', 'ề', 'ể', 'ễ', 'ệ', 'ἐ', 'ἑ', 'ἒ', 'ἓ', 'ἔ', 'ἕ', 'ὲ', 'έ', 'e', 'ℰ', 'ℇ', '∃', 'Ḕ', 'Ḗ', 'Ḙ', 'Ḛ', 'Ḝ', 'Ẹ', 'Ẻ', 'Ẽ', 'Ế', 'Ề', 'Ể', 'Ễ', 'Ệ', 'Ὲ', 'Έ', 'Ἐ', 'Ἑ', 'Ἒ', 'Ἓ', 'Ἔ', 'Ἕ', 'E'],
                    'f' => ['ⓕ', '⒡', 'ḟ', 'ƒ', 'f', 'ℱ', 'Ḟ', '₣', '℉', 'F'],
                    'g' => ['ⓖ', '⒢', '❡', 'ḡ', 'ℊ', 'g', 'ℊ', 'Ḡ', 'G'],
                    'h' => ['ⓗ', '⒣', 'ℎ', 'ℏ', 'ℌ', 'ḣ', 'ḥ', 'ḧ', 'ḩ', 'ḫ', 'ẖ', 'h', 'ℋ', 'ℍ', 'Ḣ', 'Ḥ', 'Ḧ', 'Ḩ', 'Ḫ', 'Ἠ', 'Ħ', 'Ἡ', 'Ἢ', 'Ἣ', 'Ἤ', 'Ἥ', 'Ἦ', 'Ἧ', 'ᾘ', 'ᾙ', 'ᾚ', 'ᾛ', 'ᾜ', 'ᾝ', 'ᾞ', 'ᾟ', 'Ὴ', 'Ή', 'ῌ', 'H'],
                    'i' => ['ⓘ', '⒤', 'ї', '유', 'ḭ', 'ḯ', 'ỉ', 'ị', 'ἰ', 'ἱ', 'ἲ', 'ἳ', 'ἴ', 'ἵ', 'ἶ', 'ἷ', 'ῐ', 'ῑ', 'ῒ', 'ΐ', 'ῖ', 'ῗ', 'ὶ', 'ί', 'i', 'Ї', 'ℐ', 'Ḭ', 'ḭ', 'Ḯ', 'ḯ', 'Ỉ', 'ỉ', 'Ị', 'ị', 'ἰ', 'ἱ', 'ἲ', 'ἳ', 'ἴ', 'ἵ', 'ἶ', 'ἷ', 'Ἰ', 'Ἱ', 'Ἲ', 'Ἳ', 'Ἴ', 'Ἵ', 'Ἶ', 'Ἷ', 'ῐ', 'ῑ', 'ῒ', 'ΐ ῖ', 'ῗ', 'Ῐ', 'Ῑ', 'Ὶ', 'Ί', 'ὶ', 'ί', 'I', '1'],
                    'j' => ['ⓙ', '⒥', 'ʝ', '♩', 'j', 'Ⓙ', 'J'],
                    'k' => ['ⓚ', '⒦', 'к', 'ḱ', 'ḳ', 'ḵ', 'k', '₭', 'Ḱ', 'Ḳ', 'Ḵ', 'K'],
                    'l' => ['ⓛ', '⒧', 'ℓ', 'ḻ', 'ḽ', 'l', 'ℒ', '₤', 'Ḷ', 'Ḹ', 'Ḻ', 'Ḽ', 'L'],
                    'm' => ['ⓜ', '⒨', 'Պ', 'ṃ', 'ḿ', 'ṁ', 'm', '♏', 'Ḿ', 'Ṁ', 'Ṃ', 'M', 'സ', '൬', 'ന', 'ണ', '൩'],
                    'n' => ['π', 'ⓝ', '⒩', 'η', 'ℵ', 'സ', '൩', 'ന', 'ṅ', 'ṇ', 'ṉ', 'ṋ', 'ἠ', 'ἡ', 'ἢ', 'ἣ', 'ἤ', 'ἥ', 'ἦ', 'ἧ', 'ὴ', 'ή', 'ᾐ', 'ᾑ', 'ᾒ', 'ᾓ', 'ᾔ', 'ᾕ', 'ᾖ', 'ᾗ', 'ῂ', 'ῃ', 'ῄ', 'ῆ', 'ῇ', 'n', 'ℕ', '₦', 'Ṅ', 'Ṇ', 'Ṉ', 'Ṋ', 'N'],
                    'o' => ['0', 'ṍ', 'ṏ', 'ṑ', 'ṓ', 'ọ', 'ỏ', 'ố', 'ồ', 'ổ', 'ỗ', 'ớ', 'ờ', 'ở', 'ỡ', 'ợ', 'ὀ', 'ὁ', 'ὂ', 'ὃ', 'ὄ', 'ὅ', 'ộ', 'o', 'Ṍ', 'Ṏ', 'Ṑ', 'Ṓ', 'Ọ', 'Ỏ', 'Ố', 'Ồ', 'Ổ', 'Ỗ', 'Ộ', 'Ớ', 'Ờ', 'Ở', 'Ỡ', 'Ợ', 'Ὀ', 'Ὁ', 'Ὂ', 'Ὃ', 'Ὄ', 'O'],
                    'p' => ['℘', 'ṗ', 'ṕ', 'ῥ', 'ῤ', 'p', 'ℙ', 'Ṗ', 'Ῥ', 'Ṕ', 'P'],
                    'q' => ['⒬', 'ҩ', 'ǭ', 'q', 'ℚ', 'Ǭ', 'Q'],
                    'r' => ['ⓡ', '⒭', 'Ի', 'ṟ', 'ṙ', 'ṛ', 'ṝ', 'r', 'ℛ', 'ℜ', 'ℝ', '℟', 'Ṙ', 'Ṛ', 'Ṝ', 'Ṟ', 'R'],
                    's' => ['ⓢ', '⒮', 'ട', 'ഗ', 'ṡ', 'ṣ', 'ṥ', 'ṧ', 'ṩ', 'ş', '﹩', 's', 'Š', 'Ṡ', 'Ṣ', 'Ṥ', 'Ṧ', 'Ṩ', 'S', '$'],
                    't' => ['ⓣ', '⒯', '☂', 'ṫ', 'ṭ', 'ṯ', 'ṱ', 'ẗ', '†', 't', '₮', 'Ṫ', 'Ṭ', 'Ṯ', 'Ṱ', 'T'],
                    'u' => ['υ', 'ṳ', 'ṵ', 'ṷ', 'ṹ', 'ṻ', 'ụ', 'ủ', 'ứ', 'ừ', 'ử', 'ữ', 'ự', 'ὐ', 'ὑ', 'ὒ', 'ὓ', 'ὔ', 'ὕ', 'ὖ', 'ὗ', 'ὺ', 'ύ', 'ῠ', 'ῡ', 'ῢ', 'ΰ', 'ῦ', 'ῧ', 'u', 'Ṳ', 'Ụ', 'Ủ', 'Ứ', 'Ừ', 'Ử', 'Ữ', 'Ự', 'Ṷ', 'Ṹ', 'Ṻ', 'Ṵ', 'U'],
                    'v' => ['ⓥ', '⒱', 'ṽ', 'ṿ', 'v', 'Ṽ', 'Ṿ', 'V'],
                    'w' => ['⒲', 'ഡ', 'ധ', 'ω', 'ẁ', 'ẃ', 'ẅ', 'ẇ', 'ẉ', 'ẘ', 'ὠ', 'ὡ', 'ὢ', 'ὣ', 'ὤ', 'ὥ', 'ὦ', 'ὧ', 'ὼ', 'ώ', 'ᾠ', 'ᾡ', 'ᾢ', 'ᾣ', 'ᾤ', 'ᾥ', 'ᾦ', 'ᾧ', 'ῲ', 'ῳ', 'ῴ', 'ῶ', 'ῷ', 'w', '₩', 'Ẁ', 'Ẃ', 'Ẅ', 'Ẇ', 'Ẉ', 'W'],
                    'x' => ['⒳', '✖', '✗', '✘', 'ẋ', '☠', 'ẍ', 'x', 'Ẍ', 'Ẋ', 'X'],
                    'y' => ['ഴ', 'ẙ', 'ỳ', 'ỵ', 'ỷ', 'ỹ', 'ẏ', 'y', 'ㄚ', 'Ẏ', 'Ὑ', 'Ὓ', 'Ὕ', 'Ὗ', 'Ῠ', 'Ῡ', 'Ὺ', 'Ύ', 'Ỳ', 'Ỵ', 'Ỷ', 'Ỹ', 'Y'],
                    'z' => []
                ];
                $symbols = array("!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "-", "_", "+", "=", "{", "}", "[", "]", ":", ";", ",", ".", "<", ">", "/", "?", "|");

                foreach ($letter_combinations as $letter => $letter_combination_array) {
                    foreach ($letter_combination_array as $letter_combination_item) {
                        if (preg_match($delimiter . "$letter_combination_item" . $delimiter, $word)) {
                            $word = str_replace($letter_combination_item, $letter, $word);
                        }
                    }
                }

                foreach ($symbols as $symbol) {
                    if (strpos($word, $symbol)) {
                        $word = str_replace($symbol, "", $word);
                    }
                }

                $refined_sentence .= $word . ' ';
            } else {
                $refined_sentence .= $word . ' ';
            }
        }

        //Removing white spaces from refined sentence
        $words_of_refined_sentence = explode(" ", $refined_sentence);
        foreach ($words_of_refined_sentence as $key => $item) {
            if ($item == "") {
                unset($words_of_refined_sentence[$key]);
            }
        }

        //Checking for banned words after refining sentence
        $banned_words_in_sentence[] = $this->checkingForBannedWordsInGivenSentence($black_listed_words, $delimiter, $refined_sentence, $words_of_refined_sentence);

        //removing duplicate occurrences from banned_words_in_sentence array
        return array_unique(Arr::flatten($banned_words_in_sentence));
    }

    public function maskText($string, $banned_words): string
    {
        foreach ($banned_words as $word) {
            $maskedWord = Str::mask($word, '*', 1, max(1, strlen($word) - 2));
            $string = Str::replaceFirst($word, $maskedWord, $string);
        }
        return $string;
    }

    /**
     * @param array $black_listed_words
     * @param $delimiter
     * @param mixed $sentence
     * @param array $words
     * @return array
     */
    private function checkingForBannedWordsInGivenSentence(array $black_listed_words, $delimiter, mixed $sentence, array $words): array
    {
        $banned_words_in_given_sentence=[];
        foreach ($black_listed_words as $banned_word) {
            $banned_word = strtolower($banned_word);
            $pattern = $delimiter . preg_quote($banned_word, $delimiter) . $delimiter . 'i';

            if (preg_match($pattern, $sentence)) {
                $indexes = [];
                foreach ($words as $key => $item) {
                    if (str_contains(strtolower($item), $banned_word)) {
                        $indexes[] = $key;
                    }
                }
                if (!$indexes == []) {
                    if (sizeof($indexes) > 1) {
                        foreach ($indexes as $value) {
                            $banned_words_in_given_sentence[] = $words[$value];
                        }
                    } else {
                        $index = $indexes[0];
                        $banned_words_in_given_sentence[] = $words[$index];
                    }
                }
            }
        }

        return $banned_words_in_given_sentence;
    }
}
