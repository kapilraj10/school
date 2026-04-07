<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactSetting extends Model
{
    protected $fillable = [
        'map_embed_url',
    ];

    public static function mapEmbedUrl(): string
    {
        return static::query()->latest('id')->value('map_embed_url')
            ?? 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d56516.31713633893!2d85.29111329985891!3d27.70895594415669!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39eb1854f0f6f5b9%3A0xd5f9f47f5f8f8dc9!2sKathmandu!5e0!3m2!1sen!2snp!4v1680000000000';
    }
}
