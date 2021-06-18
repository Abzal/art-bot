<?php
use App\Http\Controllers\BotManController;
use App\Project;
use App\Conversations\ProjectConversation;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;


$botman = resolve('botman');

$botman->hears('Hi(.*)', function ($bot) {
    $bot->reply('Hello!' . $bot);
});
//$botman->hears('start(.*)', BotManController::class.'@startConversation');

$botman->hears('/start', function($bot){
    $bot->startConversation(new ProjectConversation);
});
