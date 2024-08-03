<?php

namespace App\Services;

use App\Models\BlacklistedWord;
use App\Models\ProfanityCategory;
use App\Models\ProfanityWord;
use App\Models\WhitelistedWord;
use Illuminate\Support\Arr;

class TextFilterService
{
    public function filterText($user_id, $text, $moderation_categories): array
    {
        $black_listed_words = BlacklistedWord::query()->where('user_id', $user_id)->where('is_enabled', true)->get()->pluck('word')->toArray();
        $white_listed_words = WhitelistedWord::query()->where('user_id', $user_id)->where('is_enabled', true)->get()->pluck('word')->toArray();

        //TODO : Use Cache for moderation categories
        if ($moderation_categories[0] == "*" || strtolower($moderation_categories[0]) == "all") {
            $moderation_category_ids = ProfanityCategory::all()
                ->pluck('id')
                ->toArray();
        } else {
            $moderation_category_ids = ProfanityCategory::query()
                ->select('id')
                ->whereIn('profanity_category_code',$moderation_categories)
                ->get()->pluck('id');
        }

        $sentence = $text;
        $words = explode(" ", $sentence);
        $refined_sentence = "";
        $white_listed_hits = [];

        $banned_words_in_sentence = [];

        //Determining delimiter
        $all_banned_words_string = implode($black_listed_words);
        $all_delimiters = ["/", "#", "~", "!", "|", "@", "%", "&", "^", "*"];
        $charactersNotInString = array_diff($all_delimiters, str_split($all_banned_words_string));
        $delimiter = $charactersNotInString[0];

        //Removing any white listed words (direct hits)
        $words_after_removing_whitelist = array_diff($words, $white_listed_words);
        $white_listed_hits[] = array_diff($words, $words_after_removing_whitelist);
        $words = $words_after_removing_whitelist;


        //Checking for banned words before refining sentence (This is to catch for any direct hits that might be mutated when refining)
        $banned_words_in_sentence['blacklisted_words'][] = $this->checkingForBlackListedWordsInGivenSentence($black_listed_words, $delimiter, $refined_sentence, $words);
        $banned_words_in_sentence['blacklisted_words'] = Arr::flatten($banned_words_in_sentence['blacklisted_words']);


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

        //Removing any white listed words (after refining)
        $words_after_removing_whitelist = array_diff($words_of_refined_sentence, $white_listed_words);
        $white_listed_hits[] = array_diff($words_of_refined_sentence, $words_after_removing_whitelist);
        $words = $words_after_removing_whitelist;


        //Checking for banned words after refining sentence
        $banned_words_in_sentence['blacklisted_words'][] = $this->checkingForBlackListedWordsInGivenSentence($black_listed_words, $delimiter, $refined_sentence, $words);
        $banned_words_in_sentence['blacklisted_words'] = Arr::flatten($banned_words_in_sentence['blacklisted_words']);

        //TODO : Cache the profanity dataset
        //Check if sentence has words from the profanity dataset (after refining)
        //Checking for word_1 hits (single words)
        ProfanityWord::query()
            ->join('profanity_categories', 'profanity_dataset.profanity_category_id', '=', 'profanity_categories.id')
            ->select('profanity_dataset.word_1', 'profanity_dataset.profanity_category_id', 'profanity_categories.profanity_category_code')
            ->whereIn('profanity_dataset.word_1', $words)
            ->whereNull('profanity_dataset.word_2')
            ->whereNull('profanity_dataset.word_3')
            ->whereIn('profanity_dataset.profanity_category_id',$moderation_category_ids)
            ->get()->map(function ($profanity_entry) use (&$banned_words_in_sentence, &$words) {
                $banned_words_in_sentence[$profanity_entry->profanity_category_code][] = $profanity_entry->word_1;
                $words = array_diff($words, [$profanity_entry->word_1]);
            });


        //rearranging indexes of array so that it starts from 0
        $words = array_values($words);
        //Checking for word_1 and word_2 hits (2 word phrases) (after refining)
        ProfanityWord::query()
            ->join('profanity_categories', 'profanity_dataset.profanity_category_id', '=', 'profanity_categories.id')
            ->select(
                'profanity_dataset.word_1',
                'profanity_dataset.word_2',
                'profanity_dataset.profanity_category_id',
                'profanity_categories.profanity_category_code'
            )->whereNull('profanity_dataset.word_3')
            ->whereIn('profanity_dataset.profanity_category_id',$moderation_category_ids)
            ->where(function ($query) use ($words) {
                for ($i = 0; $i < count($words) - 1; $i++) {
                    $pair = [$words[$i], $words[$i + 1]];
                    $query = $query->orWhere(function ($query) use ($pair) {
                        return $query->where('profanity_dataset.word_1', $pair[0])
                            ->where('profanity_dataset.word_2', $pair[1]);
                    });
                }
                return $query;
            })->get()->map(function ($profanity_entry) use (&$banned_words_in_sentence, &$words) {
                $banned_words_in_sentence[$profanity_entry->profanity_category_code][] = $profanity_entry->word_1 . ' ' . $profanity_entry->word_2;
                $words = array_diff($words, [$profanity_entry->word_1, $profanity_entry->word_2]);
            });


        //rearranging indexes of array so that it starts from 0
        $words = array_values($words);
        //Checking for word_1 and word_2 and word_3 hits (3 word phrases) (after refining)
        ProfanityWord::query()
            ->join('profanity_categories', 'profanity_dataset.profanity_category_id', '=', 'profanity_categories.id')
            ->select(
                'profanity_dataset.word_1',
                'profanity_dataset.word_2',
                'profanity_dataset.word_3',
                'profanity_dataset.profanity_category_id',
                'profanity_categories.profanity_category_code'
            )
            ->whereIn('profanity_dataset.profanity_category_id',$moderation_category_ids)
            ->where(function ($query) use ($words) {
                for ($i = 0; $i < count($words) - 1; $i++) {
                    if (isset($words[$i], $words[$i + 1], $words[$i + 2])) {
                        $pair = [$words[$i], $words[$i + 1], $words[$i + 2]];
                        $query = $query->orWhere(function ($query) use ($pair) {
                            return $query->where('profanity_dataset.word_1', $pair[0])
                                ->where('profanity_dataset.word_2', $pair[1])
                                ->where('profanity_dataset.word_3', $pair[2]);
                        });
                    }
                }
                return $query;
            })->get()->map(function ($profanity_entry) use (&$banned_words_in_sentence, &$words) {
                $banned_words_in_sentence[$profanity_entry->profanity_category_code][] = $profanity_entry->word_1 . ' ' . $profanity_entry->word_2 . ' ' . $profanity_entry->word_3;
                $words = array_diff($words, [$profanity_entry->word_1, $profanity_entry->word_2, $profanity_entry->word_3]);
            });

        return [
            'profanity' => $banned_words_in_sentence,
            'whitelist_hits' => Arr::flatten($white_listed_hits)
        ];

    }


    /**
     * @param array $black_listed_words
     * @param $delimiter
     * @param mixed $sentence
     * @param array $words
     * @return array
     */
    private function checkingForBlackListedWordsInGivenSentence(array $black_listed_words, $delimiter, mixed $sentence, array $words): array
    {
        $banned_words_in_given_sentence = [];
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
