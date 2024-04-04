<?php

namespace App\Livewire;

use App\Models\Message;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Component;
use Filament\Forms\Form;

class ContactForm extends Component implements HasForms
{
    use InteractsWithForms;

    public Message $message;
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        $this->message = new Message();

        return $form
            ->schema([
                TextInput::make("subject")->required(),
                Textarea::make("content")->label("Message")->required(),
                TextInput::make("response_email")
                    ->label("Reply To")
                    ->placeholder("your@email.com")
                    ->email(),
            ])
            ->model($this->message)
            ->statePath("data")
            ->columns(1);
    }

    public function create(): void
    {
        if (empty($this->data["subject"]) || empty($this->data["content"])) {
            return;
        }

        Message::insert([
            "subject" => $this->data["subject"],
            "content" => $this->data["content"],
            "respond_email" => empty($this->data["response_email"])
                ? null
                : $this->data["response_email"],
            "created_at" => now(),
        ]);
        $this->form->fill();
    }

    public function render()
    {
        return view("livewire.contact-form");
    }
}
