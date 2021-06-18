<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;

class MenuConversation extends Conversation
{

protected $fio;
protected $organization;
protected $position;



    public function askMenu()
    {
        $question = Question::create('Здравствуйте. Чем я могу Вам помочь?')
        ->addButtons([
            Button::create('Населения')->value('Населения'),
            Button::create('По сервисным программным продуктам (СПП)')->value('По сервисным программным продуктам (СПП)'),
            Button::create('Сотруднику ЦОН')->value('Сотруднику ЦОН'),
            Button::create('Государственным служащим')->value('Государственным служащим'),
        ]);

        $this->ask($question, function ($answer) {
            if ($answer->getValue() == 'Населения' ) {
                $this->askPopulation();
            }
            else
            {
                $this->repeat();
            }
        });


    }

    public function askPopulation()
    {
        $question = Question::create('Выберите проект: ')
        ->addButtons([
            Button::create('ГБД РН')->value('form_1'),
            Button::create('ГБД АР')->value('form_1'),
            Button::create('ГБД ФЛ')->value('form_1'),
            Button::create('ГБД ЮЛ')->value('form_1'),
            Button::create('Е – Акимат ДДО')->value('form_1'),
            Button::create('Е – Акимат')->value('from_1'),
            Button::create('Портал «электронного правительства» ')->value('form_2'),
            Button::create('Портал «Е-лицензирование»')->value('form_3'),
            Button::create('ИС «ezSigner»')->value('form_4'),
            Button::create('Удостоверяющий центр государственных органов РК')->value('form_5'),
            Button::create('Национальный удостоверяющий центр РК (по ЭЦП)')->value('form_6'),
        ]);

        $this->ask($question, function($answer) {
            if ($answer->getValue() == 'form_1' ) {
                $this->form_1();
            } else
            {
                $this->repeat();
            }
        });


    }

    public function form_1()
    {
        $this->ask('ФИО сотрудника', function($answer) {
            $this->fio = $answer->getText();
                 $this->ask('Наименование организации', function($answer) {
                 $this->organization = $answer->getText();
                          $this->ask('Должность', function($answer) {
                             $this->position = $answer->getText();
                             $this->say('Должность - '.$fio);
                         });

                 });

        });



    }

    public function run()
    {
        $this->askMenu();
    }
}
