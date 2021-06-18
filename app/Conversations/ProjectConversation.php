<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use App\Project;
use App\Contact;
use App\TicketForm;
use App\Ticket;
use App\Handbook;

class ProjectConversation extends Conversation
{

    protected $contact;
    protected $ticketform;
    protected $ticketArr = array();
    protected $ticket;
    protected $handbookRegion; //Модель таблицы Handbook со стракой regions
    protected $projects; //Массив моделей таблицы Projects

    public function run()
    {
        $this->handbookRegion = Handbook::where('name','regions')->first();
        $this->projects = Project::all();;
        $this->askPhone();
        //$this->askRegions();

    }

    //Превый запрос, для идентификации пользователя
    protected function askPhone(){
        $this->ask('Здравствуйте. Введите номер телефона ..', function($answer){
            $phone = $answer->getText();
             if ( $this->isPhoneNumber($phone) ){

                $this->contact = Contact::where('phone',$phone)->first();
                     if($this->contact == null){
                        $this->contact = new Contact();
                        $this->contact->phone = $phone;
                        $this->askContactFistName();
                     }else {
                        $this->askMenu();
                           }
             }else{
                $this->repeat('Введите номер телефона занова...');
             }


        });
    }

protected function askProject()
    {
        $menuQText = 'Выберите проект';
        $btts = array();
        foreach($this->projects as $project){
            array_push($btts,Button::create($project->name)->value($project->name));
        }

        $question = Question::create($menuQText)->addButtons($btts);

        $this->ask($question, function ($answer) {
            $this->say($answer->getText());

            $projectRec = Project::where('name',$answer->getText())->first();

            $ticketFormRec = TicketForm::find($projectRec->form_id);

            $this->ticketform = json_decode($ticketFormRec->form,true);

             $this->ticket = new Ticket();
             $this->ticket->subject = $projectRec->name;
             $this->ticket->form_id = $projectRec->form_id;

            $this->askForm();

        });
    }

    protected $fkey;
    public function askForm()
    {

        $tfc = count( $this->ticketform );

        for( $i = 0; $i < $tfc; $i++ )
        {
            if( ( $this->ticketform[$i]['value'] ) === '' )
            {
                $this->fkey = $i;

                if(( $this->ticketform[$i]['handbook'] ) === 'region')
                {
                    $this->askRegions();
                    break;
                }

                $this->ask(($this->ticketform[$i]['row']), function($answer){
                    $result = $answer->getText();
                    $this->ticketform[$this->fkey]['value'] = $result;
                    $this->askForm();
                });
                break;
            }else if( $i == ($tfc-1) )
            {
                $this->askEnd();
            }
        }

    }


    protected $selectedRegionArray = array();

    protected function getRegionChilds($arr, $region){

        for($i=0;$i<count($arr);$i++ ){
            if($arr[$i]['name'] === $region ){
                return $arr[$i]['child'];
            }
        }

    }

    public function askRegions(){


        $regionsArr = json_decode($this->handbookRegion->reference,true);

        if( !empty($this->selectedRegionArray) ){
            for($i = 0; $i < count($this->selectedRegionArray); $i++ ){
                $regionsArr = $this->getRegionChilds($regionsArr,$this->selectedRegionArray[$i]);
            }
        }

        if (empty($regionsArr)){

            $this->ticketform[$this->fkey]['value'] = json_encode($this->selectedRegionArray,JSON_UNESCAPED_UNICODE);
            $this->selectedRegionArray = array();
            $this->askForm();
            return null;
            //return json_encode($this->selectedRegionArray,JSON_UNESCAPED_UNICODE);
        }

        $bttns = array();
        for($i = 0; $i < count($regionsArr); $i++ ){
            array_push($bttns,Button::create($regionsArr[$i]['name'])->value($regionsArr[$i]['name']));
        }

        $question = Question::create('Выберите регион..')->addButtons($bttns);


        $this->ask($question,function($answer) {
            array_push($this->selectedRegionArray,$answer->getText());
            $this->askRegions();
        });



    }


    public function askEnd(){
         $question = Question::create('Отправить заявку?')
                     ->addButtons([
                               Button::create('Отправить')->value('send'),
                               Button::create('Отменить')->value('cancel'),
                     ]);

         $this->ask($question, function ($answer) {
            if ($answer->getText() === 'send' ) {

                $this->ticket->contact_id = $this->contact->id;
                $this->ticket->ticket = json_encode($this->ticketform,JSON_UNESCAPED_UNICODE);
                $this->ticket->save();
                $this->say('Номер заявки: '.$this->ticket->id);

            }
            else
            {
                $this->repeat();
            }
        });

    }


    public function askMenu()
    {
        $menuQText = 'Здравствуйте '. $this->contact->first_name . ' ' . $this->contact->last_name . '. Чем я могу Вам помочь?';
        $question = Question::create($menuQText)
        ->addButtons([
            Button::create('Населения')->value('Населения'),
            Button::create('По сервисным программным продуктам (СПП)')->value('По сервисным программным продуктам (СПП)'),
            Button::create('Сотруднику ЦОН')->value('Сотруднику ЦОН'),
            Button::create('Государственным служащим')->value('Государственным служащим'),
        ]);

        $this->ask($question, function ($answer) {
            if ($answer->getValue() == 'Населения' ) {
                $this->say($answer->getText());
                $this->askProject();
            }
            else
            {
                $this->repeat();
            }
        });
    }


   protected function askContactFistName(){
           $this->ask('Укажите имя', function($answer){
                $fname = $answer->getText();
                $this->contact->first_name = $fname;
                $this->askContactLastName();
           });
       }

   protected function askContactLastName(){
           $this->ask('Укажите фамилию', function($answer){
                $lname = $answer->getText();
                $this->contact->last_name = $lname;
                $this->contact->account_id = 0;
                $this->contact->save();
                $this->askMenu();
           });
       }


    protected function isPhoneNumber($phone){
        return preg_match('/((8|\+7)-?)?\(?\d{3,5}\)?-?\d{1}-?\d{1}-?\d{1}-?\d{1}-?\d{1}((-?\d{1})?-?\d{1})?/', $phone);
    }

}
