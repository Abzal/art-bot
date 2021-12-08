<?php
use App\Http\Controllers\BotManController;
use App\Project;
use App\Conversations\ProjectConversation;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;



$botman = resolve('botman');



$botman->fallback(function ($bot) {


    $question = Question::create('Введите /start чтобы начать создание заявки')
        ->addButtons([
            Button::create('/start')->value('/start'),

        ]);

    $bot->ask($question, function ($answer, $con, $bot) {
        $bot->reply('Hi');
        $bot->startConversation(new ProjectConversation);
    });


});


$botman->hears('Hi(.*)', function ($bot) {
    $bot->reply('Hello!');
});

$botman->hears('/start', function($bot){
    $bot->startConversation(new ProjectConversation);
});

$botman->hears('/stop', function($bot){
$bot->userStorage()->delete();
})->stopsConversation();
