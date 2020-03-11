<?php
/**
 * Tatoeba Project, free collaborative creation of multilingual corpuses project
 * Copyright (C) 2010 HO Ngoc Phuong Trang
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace App\Model\Entity;

use Cake\ORM\Entity;
use App\Model\Entity\HashTrait;
use App\Model\CurrentUser;
use App\Lib\LanguagesLib;

class Sentence extends Entity
{
    use HashTrait;
    use LanguageNameTrait;

    protected $_virtual = [
        'lang_name',
        'dir',
        'lang_tag',
        'is_favorite',
        'is_owned_by_current_user'
    ];

    public function __construct($properties = [], $options = []) {
        parent::__construct($properties, $options);
        $hash = $properties['hash'] ?? null;
        $this->initializeHash($hash, ['lang', 'text']);
    }

    protected function _setLang($value)
    {
        $this->updateHash();
        return empty($value) ? null : $value;
    }

    protected function _setText($value)
    {
        $this->updateHash();
        return $this->_clean($value);
    }

    private function _clean($text)
    {
        // Remove whitespace and control characters at the beginning
        $text = preg_replace('/^[\p{Z}\p{Cc}]+/u', '', $text);
        // Remove whitespace and control characters at the end
        $text = preg_replace('/[\p{Z}\p{Cc}]+$/u', '', $text);
        // Strip out any byte-order mark that might be present.
        $text = preg_replace("/\xEF\xBB\xBF/", '', $text);
        // Replace any series of whitespace or control characters
        // with a single space.
        $text = preg_replace('/[\p{Z}\p{Cc}]{2,}/u', ' ', $text);
        // Normalize to NFC
        $text = \Normalizer::normalize($text, \Normalizer::FORM_C);
        // MySQL will truncate to a byte length of 1500, which may split
        // a multibyte character. To avoid this, we preemptively
        // truncate to a maximum byte length of 1500. If a multibyte
        // character would be split, the entire character will be
        // truncated.
        $text = mb_strcut($text, 0, 1500, "UTF-8");
        return $text;
    }

    protected function _getOldFormat() 
    {
        $result['Sentence'] = [
            'id' => $this->id,
            'lang' => $this->lang,
            'text' => $this->text,
            'hash' => $this->hash,
            'script' => $this->script,
            'user_id' => $this->user_id
        ];
        
        if ($this->user) {
            $result['User'] = [
                'id' => $this->user->id,
                'username' => $this->user->username,
                'image' => $this->user->image
            ];
        }

        return $result;
    }

    protected function _getLangName()
    {
        return $this->codeToNameAlone($this->lang);
    }

    protected function _getDir()
    {
        return LanguagesLib::getLanguageDirection($this->lang);
    }

    protected function _getLangTag()
    {
        return LanguagesLib::languageTag($this->lang, $this->script);
    }

    protected function _getIsFavorite()
    {
        CurrentUser::hasFavorited($this->id);
    }

    protected function _getIsOwnedByCurrentUser()
    {
        return $this->user_id === CurrentUser::get('id');
    }
}
