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
        'a' => ['α', '4', 'ⓐ', '⒜', 'ᾰ', 'ḁ', 'ἀ', 'ἁ', 'ἂ', 'ἃ', 'ἄ', 'ἅ', 'ἆ', 'ἇ', 'ạ', 'ả', 'ầ', 'ấ', 'ẩ', 'ẫ', 'ậ', 'ắ', 'ằ', 'ẳ', 'ẵ', 'ặ', 'ẚ', 'ᾱ', 'ᾲ', 'ᾳ', 'ᾴ', 'ᾶ', 'ᾷ', 'Ѧ', 'Ặ', 'Ἀ', 'Ἁ', 'Ἂ', 'Ἃ', 'Ἄ', 'Ἅ', 'Ἆ', 'Ἇ', 'Ạ', 'Ả', 'Ấ', 'Ầ', 'Ẩ', 'Ẫ', 'Ậ', 'Ắ', 'Ằ', 'Ẳ', 'Ẵ', '@'],
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

    private array $symbols = array("!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "-", "_", "+", "=", "{", "}", "[", "]", ":", ";", ",", ".", "<", ">", "/", "?", "|", "'", "ᅳ");

    public function filterText($text, $moderation_categories, $user_id = null): array
    {

        $banned_words_in_sentence = [];
        $banned_phrases_in_sentence = [];
        $grawlix = [];

        if ($user_id) {
            $black_listed_words = BlacklistedWord::query()->where('user_id', $user_id)->where('is_enabled', true)->get()->pluck('word')->toArray();
            $white_listed_words = WhitelistedWord::query()->where('user_id', $user_id)->where('is_enabled', true)->get()->pluck('word')->toArray();
        } else {
            $black_listed_words = [];
            $white_listed_words = [];
        }

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
        $white_listed_hits = [];
        $links_in_word = [];

        //Determining delimiter
        $all_banned_words_string = implode($black_listed_words);
        $all_delimiters = ["/", "#", "~", "!", "|", "@", "%", "&", "^", "*"];
        $charactersNotInString = array_diff($all_delimiters, str_split($all_banned_words_string));
        $delimiter = $charactersNotInString[0];

        //Detecting segregated words (word potentially hidden by separating the characters ; eg - B * O ** O * B * S)
        $potentially_segregated_word = [];
        $single_segregated_word = [];
        $last_key = array_key_last($words);
        foreach ($words as $key => $word) {
            if (strlen($word) == 1 || preg_match('/^[' . preg_quote(implode('', $this->symbols), $delimiter) . ']+$/', $word) || trim($word) === '') {
                $single_segregated_word[] = $word;
            } else {
                if (!empty($single_segregated_word)) {
                    $potentially_segregated_word[] = $single_segregated_word;
                    $single_segregated_word = [];
                }
            }
            if (($key == $last_key) && !empty($single_segregated_word)) {
                $potentially_segregated_word[] = $single_segregated_word;
            }
        }

        foreach ($potentially_segregated_word as $item) {
            $words[] = implode('', $item);
        }


        //Disregarding links,words that have only one character or no characters at all
        $words = array_filter($words, function ($word) use (&$links_in_word, &$potentially_segregated_word) {
            $link_identify_pattern = '/\bhttps?:\/\/\S+/i';
            if (preg_match($link_identify_pattern, $word)) {
                $links_in_word[] = $word;
                return false;
            }
            return strlen($word) != 1 && $word != "";
        });


        // Dealing with words like 'shit//mothafukin' or 'shit...fuck'
        // This doesn't catch words like 'holidays...!!!'
        $pattern = '/([' . preg_quote(implode('', $this->symbols), '/') . '])\1{1,}(?![' . preg_quote(implode('', $this->symbols), '/') . ']*$)/';
        foreach ($words as $key => $word) {
            $split = [];

            if (preg_match($pattern, $word)) {
                //Pushing the split words into the main array
                $split = preg_split($pattern, $word);
            } else if (str_contains($word, '/')) {
                $split = explode('/', $word);
            }

            if (sizeof(array_filter($split)) > 1) {
                if ($this->checkIfAllWordInDictionary($split)) {
                    //If the phrase is something like 'Good...afternoon' then we skip it since both words are available in the dictionary
                    unset($words[$key]);
                } else {
                    array_splice($words, $key - 1, 0, $split);
                }
            }
        }

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
            } else if (preg_match($delimiter . '^[a-zA-Z]{2}(?=[^a-zA-Z]*$)(?=.*[!@#$%^&*()_\-+=\{\}\[\]:;,.<>\/?|\|])' . $delimiter, $word) === 1) {
                //If word starts with two letters and has only symbols
                $grawlix[] = $word;
                unset($words[$key]);
            } else if (preg_match($delimiter . '^[\d\W_]+$' . $delimiter, $word) === 1) {
                // Checks if the string contains only digits (\d), non-word characters (\W), or underscores (_).
                $grawlix[] = $word;
                unset($words[$key]);
            }
        }

        $words_of_refined_sentence = [];
        //Refining sentence
        foreach ($words as $word) {

            $initial_word = $word;
            $refined_words = [];
            $refined_word_1 = $word;
            $refined_word_2 = $word;

            //ctype_digit checks if all the characters in a word are numbers
            if (!ctype_digit($word)) {

                //Removing replacement characters from the words
                foreach ($this->letter_combinations as $letter => $letter_combination_array) {
                    foreach ($letter_combination_array as $letter_combination_item) {
                        if (str_contains($word, $letter_combination_item)) {
                            //We create one word by replacing the symbol with the corresponding letter
                            //We create another word by replacing the symbol with an empty character.
                            $refined_words[] = str_replace($letter_combination_item, $letter, $word);
                            $refined_words[] = str_replace($letter_combination_item, '', $word);
                            $refined_word_1 = str_replace($letter_combination_item, $letter, $refined_word_1);
                            $refined_word_2 = str_replace($letter_combination_item, '', $refined_word_2);
                        }
                    }
                }

                //Removing all symbols from the words
                foreach ($this->symbols as $symbol) {
                    if (str_contains($word, $symbol)) {
                        $refined_words[] = str_replace($symbol, "", $word);
                        $refined_word_1 = str_replace($symbol, "", $refined_word_1);
                        $refined_word_2 = str_replace($symbol, "", $refined_word_2);
                    }
                }

                //removing all numbers from the words
                $refined_words[] = preg_replace('/\d/', '', $word);
                $refined_word_1 = preg_replace('/\d/', '', $refined_word_1);
                $refined_word_2 = preg_replace('/\d/', '', $refined_word_2);


                $refined_words[] = $refined_word_1;
                $refined_words[] = $refined_word_2;

                foreach ($refined_words as $refined_word) {
                    //if consecutive repeated characters are at the end of string, we create another refined word by removing all the repeated characters (eg : hellooo -> hello)
                    if (preg_match('/(.)\1{2,}$/', $refined_word)) {
                        $refined_words[] = preg_replace('/(.)\1{2,}$/', '$1', $refined_word);
                    }

                    //If word has a single dash in the middle, we break this word into to words (co-operating -> co,operating)
                    if (preg_match('/^[a-zA-Z]+-[a-zA-Z]+$/', $refined_word)) {
                        $exploded_words = explode('-', $refined_word);
                        foreach ($exploded_words as $key => $exploded_word) {
                            if ($this->checkIfAtLeastOneWordInDictionary([$exploded_word])) {
                                unset($exploded_words[$key]);
                            }
                        }
                        $refined_words = array_merge($refined_words, $exploded_words);
                    }
                }
            }


            $refined_words = array_unique($refined_words);

            //removing whitelisted words from refined_words array
            foreach ($refined_words as $key => $refined_word) {
                if ($refined_word != "") {
                    if (in_array($refined_word, $white_listed_words)) {
                        //Checking for any white listed words (after refining)
                        $white_listed_hits[] = $refined_word;
                        unset($refined_words[$key]);
                    } else if (in_array($refined_word, $black_listed_words)) {
                        //Checking for any black listed words (after refining)
                        $banned_words_in_sentence['blacklisted_words'][] = $refined_word;
                        unset($refined_words[$key]);
                    }
                } else {
                    unset($refined_words[$key]);
                }
            }

            $words_of_refined_sentence[] = [
                'initial_word' => $initial_word,
                'refined_words' => $refined_words,
            ];
        }

        //We maintain an array to keep the founds that we found in the redis
        $words_found_in_redis = [];

        //TODO : Cache the profanity dataset
        //Check if sentence has words from the profanity dataset (after refining)
        //Checking for word_1 hits (single words)
        ProfanityWord::query()
            ->join('profanity_categories', 'profanity_dataset.profanity_category_id', '=', 'profanity_categories.id')
            ->select('profanity_dataset.word_1', 'profanity_dataset.profanity_category_id', 'profanity_categories.profanity_category_code')
            ->where(function ($query) use ($words_of_refined_sentence, &$words_found_in_redis) {

                $word_search_executed = false;
                foreach ($words_of_refined_sentence as $word) {

                    //Prevent filtering for non-profanity words by cross-checking if the word exists on Redis
                    if (!$this->checkIfAtLeastOneWordInDictionary($word['refined_words'])) {

                        if (!in_array($word['initial_word'], $word['refined_words'])) {
                            $words_to_check = [$word['initial_word'], ...$word['refined_words']];
                        } else {
                            $words_to_check = $word['refined_words'];
                        }

                        foreach ($words_to_check as $word_to_check) {

                            //removing consecutive repeated characters at the end of a string
                            $word_search_executed = true;
                            $query->orWhere(function ($query) use ($word_to_check, $word) {
                                $query->where(function ($query) use ($word_to_check) {
                                    $query->whereRaw('CHAR_LENGTH(profanity_dataset.word_1) < 3')
                                        ->where('profanity_dataset.word_1', $word_to_check);
                                })->orWhere(function ($query) use ($word_to_check) {
                                    $query->whereRaw('CHAR_LENGTH(profanity_dataset.word_1) >= 3')
                                        ->whereRaw("
                                             profanity_dataset.word_1 = (
                                                 SELECT pd.word_1
                                                 FROM profanity_dataset pd
                                                 WHERE (INSTR(?, pd.word_1) > 0)
                                                 ORDER BY LENGTH(pd.word_1) DESC
                                                 LIMIT 1
                                             )", [$word_to_check]
                                        );
                                });
                            });
                        }
                    } else {
                        $words_found_in_redis[] = $word['initial_word'];
                    }
                }

                if (!$word_search_executed) {
                    $query->whereNull('profanity_dataset.word_1');
                }
            })
            ->whereNull('profanity_dataset.word_2')
            ->whereNull('profanity_dataset.word_3')
            ->whereIn('profanity_dataset.profanity_category_id', $moderation_category_ids)
            ->get()->map(function ($profanity_entry) use (&$words_of_refined_sentence, &$banned_words_in_sentence) {
                $banned_words_in_sentence[$profanity_entry->profanity_category_code][] = $profanity_entry->word_1;
            });


        //Checking for word_1 and word_2 hits (2 word phrases) (after refining)
        if (sizeof($words_of_refined_sentence) > 1) {
            ProfanityWord::query()
                ->join('profanity_categories', 'profanity_dataset.profanity_category_id', '=', 'profanity_categories.id')
                ->select(
                    'profanity_dataset.word_1',
                    'profanity_dataset.word_2',
                    'profanity_dataset.profanity_category_id',
                    'profanity_categories.profanity_category_code'
                )->whereNull('profanity_dataset.word_3')
                ->whereIn('profanity_dataset.profanity_category_id', $moderation_category_ids)
                ->where(function ($query) use ($words_of_refined_sentence) {
                    for ($i = 0; $i < count($words_of_refined_sentence) - 1; $i++) {
                        $pair = [$words_of_refined_sentence[$i]['refined_words'], $words_of_refined_sentence[$i + 1]['refined_words']];
                        $query = $query->orWhere(function ($query) use ($pair) {
                            return $query->whereIn('profanity_dataset.word_1', $pair[0])
                                ->whereIn('profanity_dataset.word_2', $pair[1]);
                        });
                    }
                    return $query;
                })->get()->map(function ($profanity_entry) use (&$banned_phrases_in_sentence, &$words) {
                    $banned_phrases_in_sentence[$profanity_entry->profanity_category_code][] = $profanity_entry->word_1 . ' ' . $profanity_entry->word_2;
                });
        }

        if (sizeof($words_of_refined_sentence) > 2) {
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
                ->where(function ($query) use ($words_of_refined_sentence) {
                    for ($i = 0; $i < count($words_of_refined_sentence) - 1; $i++) {
                        if (isset($words_of_refined_sentence[$i], $words_of_refined_sentence[$i + 1], $words_of_refined_sentence[$i + 2])) {
                            $pair = [$words_of_refined_sentence[$i]['refined_words'], $words_of_refined_sentence[$i + 1]['refined_words'], $words_of_refined_sentence[$i + 2]['refined_words']];
                            $query = $query->orWhere(function ($query) use ($pair) {
                                return $query->whereIn('profanity_dataset.word_1', $pair[0])
                                    ->whereIn('profanity_dataset.word_2', $pair[1])
                                    ->whereIn('profanity_dataset.word_3', $pair[2]);
                            });
                        }
                    }
                    return $query;
                })->get()->map(function ($profanity_entry) use (&$banned_phrases_in_sentence, &$words) {
                    $banned_phrases_in_sentence[$profanity_entry->profanity_category_code][] = $profanity_entry->word_1 . ' ' . $profanity_entry->word_2 . ' ' . $profanity_entry->word_3;
                });
        }

        //Removing any duplicate blacklisted words
        $banned_words_in_sentence['blacklisted_words'] = array_unique(Arr::flatten($banned_words_in_sentence['blacklisted_words']));

        //Removing duplicates of other banned words
        foreach ($banned_words_in_sentence as $key => $banned_words_array) {
            if ($key != 'blacklisted_words') {
                usort($banned_words_array, function ($a, $b) {
                    return strlen($b) - strlen($a);
                });

                $filteredWords = [];

                foreach ($banned_words_array as $word) {
                    $isContained = false;

                    foreach ($filteredWords as $filteredWord) {
                        if (str_contains($filteredWord, $word)) {
                            $isContained = true;
                            break;
                        }
                    }

                    if (!$isContained) {
                        $filteredWords[] = $word;

                    }
                }
                $banned_words_in_sentence[$key] = $filteredWords;
            }
        }

        // Unsetting words with low identifiably and adding the original word (word in sentence) along with the detected word
        $banned_word_with_original_word = [];
        foreach ($banned_words_in_sentence as $key => $items) {
            foreach ($items as $word) {
                $original_words = $this->findOriginalWords($words_of_refined_sentence, $word);

                foreach ($original_words as $original_word) {
                    if (preg_match('/^[a-zA-Z]+-[a-zA-Z]+$/', $original_word)) {
                        //If word has hyphen (-)
                        $banned_word_with_original_word[$key][] = [
                            'flagged_word' => $word,
                            'sentence_token' => $original_word
                        ];
                    } else {
                        if ($this->isWordIdentifiable($original_word, $word) && !in_array($original_word, $words_found_in_redis)) {
                            $banned_word_with_original_word[$key][] = [
                                'flagged_word' => $word,
                                'sentence_token' => $original_word
                            ];
                        }
                    }
                }
            }
        }

        //Setting an empty blacklisted_words key if it is not available (to maintain API consistency)
        if (!isset($banned_word_with_original_word['blacklisted_words'])) {
            $banned_word_with_original_word['blacklisted_words'] = [];
        }

        return [
            'profanity' => [
                'words' => $banned_word_with_original_word,
                'phrases' => $banned_phrases_in_sentence
            ],
            'whitelist_hits' => Arr::flatten($white_listed_hits),
            'links' => $links_in_word,
            'grawlix' => $grawlix
        ];
    }

    private function checkIfAtLeastOneWordInDictionary(array $words): bool
    {
        if (empty($words)) {
            return false;
        }

        foreach ($words as $item) {
            if (Redis::sismember('words', strtolower($item))) {
                return true;
            }
        }

        return false;
    }

    private function checkIfAllWordInDictionary(array $words): bool
    {
        if (empty($words)) {
            return false;
        }

        foreach ($words as $item) {
            if (!Redis::sismember('words', strtolower($item))) {
                return false;
            }
        }

        return true;
    }


    private function findOriginalWords($original_words_array, $word): array
    {
        $original_words = [];

        foreach ($original_words_array as $item) {
            foreach ($item['refined_words'] as $refined_word) {
                if (strlen($word) < 3) {
                    //if refined word has less than 3 characters, It should be an exact match
                    if ($refined_word == $word) {
                        $original_words[] = $item['initial_word'];
                        break;
                    }
                } else {
                    //If refined word has more than 3 characters it can be an exact match or a substring
                    if ($refined_word == $word) {
                        $original_words[] = $item['initial_word'];
                        break;
                    } else if (preg_match('/' . $word . '/i', $refined_word)) {
                        $original_words[] = $item['initial_word'];
                        break;
                    }
                }
            }
        }

        return $original_words;
    }

    function isWordIdentifiable($word, $substring): bool
    {
        // Convert the word to lowercase and the substring to lowercase
        $word = strtolower($word);
        $substring = strtolower($substring);

        // Find the position of the substring within the word
        $position = strpos($word, $substring);

        // If the substring is not found, we return true (eg : original word is _a_sᅳ_s_ and the banned word caught is ass)
        if ($position === false) {
            return true;
        }

        // Extract the portion of the word before and after the substring
        $beforeSubstring = substr($word, 0, $position);
        $afterSubstring = substr($word, $position + strlen($substring));

        // Count only the letters in the portions before and after the substring
        $letterCountBefore = 0;
        $letterCountAfter = 0;

        for ($i = 0; $i < strlen($beforeSubstring); $i++) {
            if (ctype_alpha($beforeSubstring[$i])) {
                $letterCountBefore++;
            }
        }

        for ($i = 0; $i < strlen($afterSubstring); $i++) {
            if (ctype_alpha($afterSubstring[$i])) {
                $letterCountAfter++;
            }
        }

        return ($letterCountBefore < 3 || $letterCountAfter < 3);
    }
}
