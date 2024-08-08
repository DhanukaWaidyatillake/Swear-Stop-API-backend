<?php

namespace App\Services;

use App\Models\BlacklistedWord;
use App\Models\ProfanityCategory;
use App\Models\ProfanityWord;
use App\Models\WhitelistedWord;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Redis;

class TextFilterService
{

    private array $letter_combinations = [
        'a' => ['α', '4', 'ⓐ', '⒜', 'ᾰ', 'ḁ', 'ἀ', 'ἁ', 'ἂ', 'ἃ', 'ἄ', 'ἅ', 'ἆ', 'ἇ', 'ạ', 'ả', 'ầ', 'ấ', 'ẩ', 'ẫ', 'ậ', 'ắ', 'ằ', 'ẳ', 'ẵ', 'ặ', 'ẚ', 'ᾱ', 'ᾲ', 'ᾳ', 'ᾴ', 'ᾶ', 'ᾷ', 'Ѧ', 'Ặ', 'Ἀ', 'Ἁ', 'Ἂ', 'Ἃ', 'Ἄ', 'Ἅ', 'Ἆ', 'Ἇ', 'Ạ', 'Ả', 'Ấ', 'Ầ', 'Ẩ', 'Ẫ', 'Ậ', 'Ắ', 'Ằ', 'Ẳ', 'Ẵ'],
        'b' => ['⒝', 'ദ', '൫', '♭', 'ḃ', 'ḅ', 'ḇ', 'ℬ', 'Ḃ', 'Ḅ', 'Ḇ'],
        'c' => ['ⓒ', '⒞', 'ḉ', 'ℂ', 'ℭ', '℃', '₡', '∁'],
        'd' => ['ⓓ', '⒟', 'ⅾ', 'ḋ', 'ḍ', 'ḏ', 'ḑ', 'ḓ', 'Ḓ', 'Ḋ', 'Ḍ', 'Ḏ', 'Ḑ'],
        'e' => ['🄴', '3', 'ⓔ', '⒠', 'ℯ', '∊', '€', 'ḕ', 'ḗ', 'ḙ', 'ḛ', 'ḝ', 'ẹ', 'ẻ', 'ẽ', 'ế', 'ề', 'ể', 'ễ', 'ệ', 'ἐ', 'ἑ', 'ἒ', 'ἓ', 'ἔ', 'ἕ', 'ὲ', 'έ', 'ℰ', 'ℇ', '∃', 'Ḕ', 'Ḗ', 'Ḙ', 'Ḛ', 'Ḝ', 'Ẹ', 'Ẻ', 'Ẽ', 'Ế', 'Ề', 'Ể', 'Ễ', 'Ệ', 'Ὲ', 'Έ', 'Ἐ', 'Ἑ', 'Ἒ', 'Ἓ', 'Ἔ', 'Ἕ'],
        'f' => ['ⓕ', '⒡', 'ḟ', 'ƒ', 'ℱ', 'Ḟ', '₣', '℉'],
        'g' => ['ⓖ', '⒢', '❡', 'ḡ', 'ℊ', 'ℊ', 'Ḡ'],
        'h' => ['ⓗ', '⒣', 'ℎ', 'ℏ', 'ℌ', 'ḣ', 'ḥ', 'ḧ', 'ḩ', 'ḫ', 'ẖ', 'ℋ', 'ℍ', 'Ḣ', 'Ḥ', 'Ḧ', 'Ḩ', 'Ḫ', 'Ἠ', 'Ħ', 'Ἡ', 'Ἢ', 'Ἣ', 'Ἤ', 'Ἥ', 'Ἦ', 'Ἧ', 'ᾘ', 'ᾙ', 'ᾚ', 'ᾛ', 'ᾜ', 'ᾝ', 'ᾞ', 'ᾟ', 'Ὴ', 'Ή', 'ῌ'],
        'i' => ['!', 'ⓘ', '⒤', 'ї', '유', 'ḭ', 'ḯ', 'ỉ', 'ị', 'ἰ', 'ἱ', 'ἲ', 'ἳ', 'ἴ', 'ἵ', 'ἶ', 'ἷ', 'ῐ', 'ῑ', 'ῒ', 'ΐ', 'ῖ', 'ῗ', 'ὶ', 'ί', 'Ї', 'ℐ', 'Ḭ', 'ḭ', 'Ḯ', 'ḯ', 'Ỉ', 'ỉ', 'Ị', 'ị', 'ἰ', 'ἱ', 'ἲ', 'ἳ', 'ἴ', 'ἵ', 'ἶ', 'ἷ', 'Ἰ', 'Ἱ', 'Ἲ', 'Ἳ', 'Ἴ', 'Ἵ', 'Ἶ', 'Ἷ', 'ῐ', 'ῑ', 'ῒ', 'ΐ ῖ', 'ῗ', 'Ῐ', 'Ῑ', 'Ὶ', 'Ί', 'ὶ', 'ί', '1'],
        'j' => ['ⓙ', '⒥', 'ʝ', '♩', 'Ⓙ'],
        'k' => ['ⓚ', '⒦', 'к', 'ḱ', 'ḳ', 'ḵ', '₭', 'Ḱ', 'Ḳ', 'Ḵ'],
        'l' => ['ⓛ', '⒧', 'ℓ', 'ḻ', 'ḽ', 'ℒ', '₤', 'Ḷ', 'Ḹ', 'Ḻ', 'Ḽ'],
        'm' => ['ⓜ', '⒨', 'Պ', 'ṃ', 'ḿ', 'ṁ', '♏', 'Ḿ', 'Ṁ', 'Ṃ', 'സ', '൬', 'ന', 'ണ', '൩'],
        'n' => ['π', 'ⓝ', '⒩', 'η', 'ℵ', 'സ', '൩', 'ന', 'ṅ', 'ṇ', 'ṉ', 'ṋ', 'ἠ', 'ἡ', 'ἢ', 'ἣ', 'ἤ', 'ἥ', 'ἦ', 'ἧ', 'ὴ', 'ή', 'ᾐ', 'ᾑ', 'ᾒ', 'ᾓ', 'ᾔ', 'ᾕ', 'ᾖ', 'ᾗ', 'ῂ', 'ῃ', 'ῄ', 'ῆ', 'ῇ', 'ℕ', '₦', 'Ṅ', 'Ṇ', 'Ṉ', 'Ṋ'],
        'o' => ['0', 'ṍ', 'ṏ', 'ṑ', 'ṓ', 'ọ', 'ỏ', 'ố', 'ồ', 'ổ', 'ỗ', 'ớ', 'ờ', 'ở', 'ỡ', 'ợ', 'ὀ', 'ὁ', 'ὂ', 'ὃ', 'ὄ', 'ὅ', 'ộ', 'Ṍ', 'Ṏ', 'Ṑ', 'Ṓ', 'Ọ', 'Ỏ', 'Ố', 'Ồ', 'Ổ', 'Ỗ', 'Ộ', 'Ớ', 'Ờ', 'Ở', 'Ỡ', 'Ợ', 'Ὀ', 'Ὁ', 'Ὂ', 'Ὃ', 'Ὄ'],
        'p' => ['℘', 'ṗ', 'ṕ', 'ῥ', 'ῤ', 'ℙ', 'Ṗ', 'Ῥ', 'Ṕ'],
        'q' => ['⒬', 'ҩ', 'ǭ', 'ℚ', 'Ǭ'],
        'r' => ['ⓡ', '⒭', 'Ի', 'ṟ', 'ṙ', 'ṛ', 'ṝ', 'ℛ', 'ℜ', 'ℝ', '℟', 'Ṙ', 'Ṛ', 'Ṝ', 'Ṟ'],
        's' => ['ⓢ', '⒮', 'ട', 'ഗ', 'ṡ', 'ṣ', 'ṥ', 'ṧ', 'ṩ', 'ş', '﹩', 'Š', 'Ṡ', 'Ṣ', 'Ṥ', 'Ṧ', 'Ṩ', '$'],
        't' => ['ⓣ', '⒯', '☂', 'ṫ', 'ṭ', 'ṯ', 'ṱ', 'ẗ', '†', '₮', 'Ṫ', 'Ṭ', 'Ṯ', 'Ṱ'],
        'u' => ['υ', 'ṳ', 'ṵ', 'ṷ', 'ṹ', 'ṻ', 'ụ', 'ủ', 'ứ', 'ừ', 'ử', 'ữ', 'ự', 'ὐ', 'ὑ', 'ὒ', 'ὓ', 'ὔ', 'ὕ', 'ὖ', 'ὗ', 'ὺ', 'ύ', 'ῠ', 'ῡ', 'ῢ', 'ΰ', 'ῦ', 'ῧ', 'Ṳ', 'Ụ', 'Ủ', 'Ứ', 'Ừ', 'Ử', 'Ữ', 'Ự', 'Ṷ', 'Ṹ', 'Ṻ', 'Ṵ'],
        'v' => ['ⓥ', '⒱', 'ṽ', 'ṿ', 'Ṽ', 'Ṿ'],
        'w' => ['⒲', 'ഡ', 'ധ', 'ω', 'ẁ', 'ẃ', 'ẅ', 'ẇ', 'ẉ', 'ẘ', 'ὠ', 'ὡ', 'ὢ', 'ὣ', 'ὤ', 'ὥ', 'ὦ', 'ὧ', 'ὼ', 'ώ', 'ᾠ', 'ᾡ', 'ᾢ', 'ᾣ', 'ᾤ', 'ᾥ', 'ᾦ', 'ᾧ', 'ῲ', 'ῳ', 'ῴ', 'ῶ', 'ῷ', '₩', 'Ẁ', 'Ẃ', 'Ẅ', 'Ẇ', 'Ẉ'],
        'x' => ['⒳', '✖', '✗', '✘', 'ẋ', '☠', 'ẍ', 'Ẍ', 'Ẋ'],
        'y' => ['ഴ', 'ẙ', 'ỳ', 'ỵ', 'ỷ', 'ỹ', 'ẏ', 'ㄚ', 'Ẏ', 'Ὑ', 'Ὓ', 'Ὕ', 'Ὗ', 'Ῠ', 'Ῡ', 'Ὺ', 'Ύ', 'Ỳ', 'Ỵ', 'Ỷ', 'Ỹ'],
        'z' => []
    ];

    private array $symbols = array("!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "-", "_", "+", "=", "{", "}", "[", "]", ":", ";", ",", ".", "<", ">", "/", "?", "|");
    private array $numbers = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 0);

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
                ->whereIn('profanity_category_code', $moderation_categories)
                ->get()->pluck('id');
        }

        $sentence = $text;
        $words = explode(" ", $sentence);
        $refined_sentence = "";
        $white_listed_hits = [];

        $words = array_filter($words, function ($word) {
            return strlen($word) != 1;
        });

        $banned_words_in_sentence = [];
        $grawlix = [];

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
        $banned_words_in_sentence['blacklisted_words'][] = array_intersect($words, $black_listed_words);
        $banned_words_in_sentence['blacklisted_words'] = Arr::flatten($banned_words_in_sentence['blacklisted_words']);


        //Checking for grawlix
        foreach ($words as $key => $word) {
            if (preg_match($delimiter . '^(?=[^a-zA-Z]*[a-zA-Z][^a-zA-Z]*$)(?=.*[!@#$%^&*()_\-+=\{\}\[\]:;,.<>\/?|\|])' . $delimiter, $word) === 1) {
                //If word starts with a letter and has only symbols
                $grawlix[] = $word;
                unset($words[$key]);
            } else if (preg_match($delimiter . '^[\d\W_]+$' . $delimiter, $word) === 1) {
                //If a word has only symbols and letters
                $grawlix[] = $word;
                unset($words[$key]);
            }
        }


        //Refining sentence
        foreach ($words as $word) {
            if (!ctype_digit($word)) {

                //Removing replacement characters from the words
                foreach ($this->letter_combinations as $letter => $letter_combination_array) {
                    foreach ($letter_combination_array as $letter_combination_item) {
                        if (str_contains($word, $letter_combination_item)) {
                            if (in_array($letter_combination_item, $this->symbols)) {
                                //We create one word by replacing the symbol with the corresponding letter
                                //We create another word by replacing the symbol with a empty character.
                                //We then combine these two words with a space in between to create the new word
                                $word = str_replace($letter_combination_item, $letter, $word) . ' ' . str_replace($letter_combination_item, '', $word);
                            } else {
                                $word = str_replace($letter_combination_item, $letter, $word);
                            }
                        }
                    }
                }

                //Removing all symbols from the words
                foreach ($this->symbols as $symbol) {
                    if (strpos($word, $symbol)) {
                        $word = str_replace($symbol, "", $word);
                    }
                }

                //removing all numbers from the words
                foreach ($this->numbers as $number) {
                    if (strpos($word, $number)) {
                        $word = str_replace($number, "", $word);
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
        $banned_words_in_sentence['blacklisted_words'][] = array_intersect($words, $black_listed_words);
        $banned_words_in_sentence['blacklisted_words'] = array_unique(Arr::flatten($banned_words_in_sentence['blacklisted_words']));


        //TODO : Cache the profanity dataset
        //Check if sentence has words from the profanity dataset (after refining)
        //Checking for word_1 hits (single words)
        ProfanityWord::query()
            ->join('profanity_categories', 'profanity_dataset.profanity_category_id', '=', 'profanity_categories.id')
            ->select('profanity_dataset.word_1', 'profanity_dataset.profanity_category_id', 'profanity_categories.profanity_category_code')
            ->where(function ($query) use ($words) {
                foreach ($words as $word) {
                    //Prevent filtering for non-profanity words by cross-checking if the word exists on Redis
                    if (!Redis::sismember('words', $word)) {
                        $query->orWhere(function ($query) use ($word) {
                            //If there is no exact match, then we check using the INSTR function
                            $query->where('profanity_dataset.word_1', $word)->orWhereRaw("
                                profanity_dataset.word_1 = (
                                    SELECT pd.word_1
                                    FROM profanity_dataset pd
                                    WHERE (INSTR(?, pd.word_1) > 0)
                                    ORDER BY LENGTH(pd.word_1) DESC
                                    LIMIT 1
                                )", [$word]
                            );
                        });
                    }
                }
            })
            ->whereNull('profanity_dataset.word_2')
            ->whereNull('profanity_dataset.word_3')
            ->whereIn('profanity_dataset.profanity_category_id', $moderation_category_ids)
            ->get()->map(function ($profanity_entry) use (&$banned_words_in_sentence, &$words) {
                $banned_words_in_sentence[$profanity_entry->profanity_category_code][] = $profanity_entry->word_1;
                $words = array_diff($words, [$profanity_entry->word_1]);
            });


        //rearranging indexes of array so that it starts from 0
        $words = array_values($words);
        //Checking for word_1 and word_2 hits (2 word phrases) (after refining)
        if (sizeof($words) > 1) {
            ProfanityWord::query()
                ->join('profanity_categories', 'profanity_dataset.profanity_category_id', '=', 'profanity_categories.id')
                ->select(
                    'profanity_dataset.word_1',
                    'profanity_dataset.word_2',
                    'profanity_dataset.profanity_category_id',
                    'profanity_categories.profanity_category_code'
                )->whereNull('profanity_dataset.word_3')
                ->whereIn('profanity_dataset.profanity_category_id', $moderation_category_ids)
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
        }

        if (sizeof($words) > 2) {
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
                ->whereIn('profanity_dataset.profanity_category_id', $moderation_category_ids)
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
        }

        return [
            'profanity' => $banned_words_in_sentence,
            'whitelist_hits' => Arr::flatten($white_listed_hits),
            'grawlix' => $grawlix
        ];

    }
}
