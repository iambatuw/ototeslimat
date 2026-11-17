<?php

namespace App\Mail;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/** Mağaza sahibine giden günlük özet: dün ne oldu, bugün neye bakmalı. */
class DailySummary extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $day, public Collection $troubled, public Collection $lowStock) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: Setting::brand().' günlük özet, '.$this->day['date']->format('d.m.Y'));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.daily-summary', with: ['brand' => Setting::brand()]);
    }
}
