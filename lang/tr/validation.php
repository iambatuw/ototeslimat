<?php

return [
    'accepted' => ':Attribute kabul edilmeli.',
    'between' => [
        'numeric' => ':Attribute :min ile :max arasında olmalı.',
        'string' => ':Attribute :min ile :max karakter arasında olmalı.',
        'file' => ':Attribute :min ile :max KB arasında olmalı.',
    ],
    'boolean' => ':Attribute yalnızca evet ya da hayır olabilir.',
    'confirmed' => ':Attribute tekrarı eşleşmiyor.',
    'current_password' => 'Mevcut şifre yanlış.',
    'email' => ':Attribute geçerli bir e-posta adresi olmalı.',
    'exists' => 'Seçilen :attribute bulunamadı.',
    'file' => ':Attribute bir dosya olmalı.',
    'in' => 'Seçilen :attribute geçerli değil.',
    'integer' => ':Attribute tam sayı olmalı.',
    'lowercase' => ':Attribute küçük harf olmalı.',
    'max' => [
        'numeric' => ':Attribute en fazla :max olabilir.',
        'string' => ':Attribute en fazla :max karakter olabilir.',
        'file' => ':Attribute en fazla :max KB olabilir.',
    ],
    'min' => [
        'numeric' => ':Attribute en az :min olmalı.',
        'string' => ':Attribute en az :min karakter olmalı.',
        'file' => ':Attribute en az :min KB olmalı.',
    ],
    'password' => [
        'letters' => ':Attribute en az bir harf içermeli.',
        'mixed' => ':Attribute en az bir büyük ve bir küçük harf içermeli.',
        'numbers' => ':Attribute en az bir rakam içermeli.',
        'symbols' => ':Attribute en az bir sembol içermeli.',
        'uncompromised' => 'Bu şifre daha önce sızan bir listede görünüyor. Başka bir şifre seç.',
    ],
    'regex' => ':Attribute biçimi geçersiz.',
    'required' => ':Attribute gerekli.',
    'required_if' => ':Attribute gerekli.',
    'string' => ':Attribute metin olmalı.',
    'unique' => 'Bu :attribute zaten kullanılıyor.',

    'attributes' => [
        'name' => 'ad',
        'email' => 'e-posta',
        'password' => 'şifre',
        'current_password' => 'mevcut şifre',
        'key' => 'anahtar',
        'platform' => 'platform',
        'mafile' => 'maFile',
        'shared_secret' => 'shared_secret',
    ],
];
