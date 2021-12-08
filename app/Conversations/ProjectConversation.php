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
use App\MainProject;
use Illuminate\Support\Facades\Log;

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
        $this->projects = Project::all();
        $this->askPhone();

    }

    //Превый запрос, для идентификации пользователя
    protected function askPhone(){
        $this->ask('Здравствуйте. Введите номер телефона в формате 87051234567..', function($answer){
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
                $this->repeat('Введите номер телефона занова в формате 87051234567...');
             }


        });
    }

protected function askProject($main_project_id)
    {
        $menuQText = 'Выберите проект';
        error_log($main_project_id);
        $projects = Project::where('main_project_id',$main_project_id)->get();

        $btts = array();
        foreach($projects as $project){
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

                    if( $this->check( $this->ticketform[$this->fkey]['validation'] , $result ) ){
                        $this->ticketform[$this->fkey]['value'] = $result;
                        $this->askForm();
                    }else{
                        $this->repeat();
                    }


                });
                break;
            }else if( $i == ($tfc-1) )
            {
                $this->askEnd();
            }
        }

    }


    protected function check($casestr,$textstr){
                    switch ($casestr) {
                        case "latters":
                            return $this->isString($textstr);
                        case "number":
                            return $this->isNumber($textstr);
                        case "iin":
                            return $this->isIin($textstr);
                        case "email":
                            return $this->isEmail($textstr);
                        case "phone":
                            return $this->isPhoneNumber($textstr);
                        default:
                           return true;
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

        $value='';

        foreach ($this->selectedRegionArray as $selectedRegion) {

          $value = (($value==='')?'':$value. '|') . $selectedRegion;
        }

            $this->ticketform[$this->fkey]['value'] = $value;
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



                $this->ticket->contact_phone = $this->contact->phone;
                $this->ticket->contact_fio = $this->contact->first_name . ' ' . $this->contact->last_name;
                $this->ticket->ticket = json_encode($this->ticketform,JSON_UNESCAPED_UNICODE);
                $this->ticket->status_id = 2;
                $this->ticket->channel = "виджет";
                $this->ticket->created_at = date("Y-m-d H:i:s");




                $this->ticket->save();

                $this->say('Номер заявки: '.$this->ticket->id);

            }
            else
            {
                $this->repeat();
            }
        });

    }

    protected $mainProjectArrNameId = array();
    public function askMenu()
    {
        $menuQText = 'Здравствуйте '. $this->contact->first_name . ' ' . $this->contact->last_name . '. Чем я могу Вам помочь?';

        $allmp = MainProject::all();
        $btts = array();
        foreach($allmp as $project){
            $this->mainProjectArrNameId[$project->name] = $project->id;
            array_push($btts,Button::create($project->name)->value($project->name));
        }

        $question = Question::create($menuQText)
        ->addButtons($btts);

        $this->ask($question, function ($answer) {

//error_log('->>' . $this->mainProjectArrNameId[$answer->getValue()] . '<<-');

            if (array_key_exists($answer->getValue(),$this->mainProjectArrNameId) ) {
                $this->say($answer->getText());
                $this->askProject($this->mainProjectArrNameId[$answer->getValue()]);
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
        return preg_match('/^((8))(\d{3}?)?[\d]{7}$/', $phone);
    }

    protected function isIin($iin){
        return preg_match('/^[0-9]{12}$/', $iin);
    }

    protected function isNumber($number){
        return preg_match('/^[0-9]*$/', $number);
    }

    protected function isString($string){
        return preg_match('/^[a-zA-Zа-яА-Я ]{2,}$/', $string);
    }

    protected function isEmail($email){
        return preg_match('/.+\@.+\..+/', $email);
    }

}
