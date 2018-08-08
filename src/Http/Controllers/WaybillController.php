<?php
namespace Rosshristov\Econt\Http\Controllers;

use App;
use App\Http\Controllers\Controller;
use DateTime;
use Input;
use League\Flysystem\Exception;
use Rosshristov\Econt\Components\CourierRequest;
use Rosshristov\Econt\Components\Instruction;
use Rosshristov\Econt\Components\Loading;
use Rosshristov\Econt\Components\Payment;
use Rosshristov\Econt\Components\Receiver;
use Rosshristov\Econt\Components\Sender;
use Rosshristov\Econt\Components\Services;
use Rosshristov\Econt\Components\Shipment;
use Rosshristov\Econt\Econt;
use Rosshristov\Econt\Exceptions\EcontException;
use Rosshristov\Econt\Http\Requests\CalculateRequest;
use Rosshristov\Econt\Http\Requests\WaybillRequest;
use Rosshristov\Econt\Models\Office;
use Rosshristov\Econt\Models\Settlement;
use Rosshristov\Econt\Models\Street;
use Rosshristov\Econt\Waybill;

class WaybillController extends Controller
{

    public function issue(WaybillRequest $request)
    {
        $this->request = $request;
        $sender = $this->_sender();
        $receiver = $this->_receiver();
        $shipment = $this->_shipment();
        $courier = $this->_courier($shipment);
        $payment = $this->_payment();
        $services = $this->_services();

        App::make(Econt::class);


        $loading = new Loading($sender, $receiver, $shipment, $payment, $services, $courier);

        $instruction = new Instruction;
        $instruction->type = Instruction::TYPE_RETURN;
        $instruction->delivery_fail_action = Instruction::FAIL_ACTION_RETURN_SENDER;

        switch ($this->request->input('shipment.instruction_returns')) {
            case Shipment::RETURNS:
                $instruction->reject_delivery_payment_side = Receiver::SIDE;
                $instruction->reject_return_payment_side = Sender::SIDE;
                break;

            case Shipment::SHIPPING_RETURNS:
                $instruction->reject_delivery_payment_side = Sender::SIDE;
                $instruction->reject_return_payment_side = Sender::SIDE;
                break;

            default:
                $instruction->reject_delivery_payment_side = Receiver::SIDE;
                $instruction->reject_return_payment_side = Receiver::SIDE;
                break;
        }

        $decline_delivery = $this->request->input('instructions.reject_delivery_payment_side');
        $decline_returns = $this->request->input('instructions.reject_return_payment_side');

        if ($decline_delivery && in_array($decline_delivery, [Sender::SIDE, Receiver::SIDE])) {
            $instruction->reject_delivery_payment_side = $decline_delivery;
        }

        if ($decline_returns && in_array($decline_returns, [Sender::SIDE, Receiver::SIDE])) {
            $instruction->reject_return_payment_side = $decline_returns;
        }


        $loading->instructions = ['e' => $instruction];

        return Waybill::issue($loading);
    }

    public function calculate(CalculateRequest $request)
    {
        $sender = $this->_senderCalc();
        $receiver = $this->_receiverCalc();
        $shipment = $this->_shipment();
        $payment = $this->_payment();
        $services = $this->_services();

        App::make(Econt::class);

        $loading = new Loading($sender, $receiver, $shipment, $payment, $services);

        return Waybill::calculate($loading);
    }

    protected function _sender()
    {
        $settlement = Settlement::find((int)$this->request->input('sender.settlement'));

        $sender = new Sender;
        $sender->name = $this->request->input('sender.name');
        $sender->name_person = $this->request->input('sender.name_person');
        $sender->city = $settlement->name;
        $sender->post_code = $settlement->post_code;
        $sender->phone_num = $this->request->input('sender.phone');

        switch ($this->request->input('sender.pickup')) {
            case 'address':
                $sender->street = Street::find((int)$this->request->input('sender.street'))->name;
                $sender->street_num = $this->request->input('sender.street_num');
                $sender->street_vh = $this->request->input('sender.street_vh');
                $sender->street_et = $this->request->input('sender.street_et');
                $sender->street_ap = $this->request->input('sender.street_ap');
                $sender->street_other = $this->request->input('sender.street_other');
                break;

            case 'office':
                $sender->office_code = Office::find((int)$this->request->input('sender.office'))->code;
                break;
        }

        return $sender;
    }

    protected function _senderCalc()
    {
        $settlement = Settlement::find((int)$this->request->input('sender.settlement'));

        $sender = new Sender;
        $sender->city = $settlement->name;
        $sender->post_code = $settlement->post_code;

        switch ($this->request->input('sender.pickup')) {
            case 'office':
                $sender->office_code = Office::find((int)$this->request->input('sender.office'))->code;
                break;
        }

        return $sender;
    }

    protected function _receiver()
    {
        $settlement = Settlement::find((int)$this->request->input('receiver.settlement'));

        $receiver = new Receiver;
        $receiver->name = $this->request->input('receiver.name');
        $receiver->city = $settlement->name;
        $receiver->post_code = $settlement->post_code;
        $receiver->phone_num = $this->request->input('receiver.phone');

        switch ($this->request->input('receiver.pickup')) {
            case 'address':
                $receiver->street = Street::find((int)$this->request->input('receiver.street'))->name;
                $receiver->street_num = $this->request->input('receiver.street_num');
                $receiver->street_vh = $this->request->input('receiver.street_vh');
                $receiver->street_et = $this->request->input('receiver.street_et');
                $receiver->street_ap = $this->request->input('receiver.street_ap');
                $receiver->street_other = $this->request->input('receiver.street_other');
                break;

            case 'office':
                $receiver->office_code = Office::find((int)$this->request->input('receiver.office'))->code;
                break;
        }

        return $receiver;
    }

    protected function _receiverCalc()
    {
        $receiver = new Receiver;
        $receiver->city = $this->request->input('receiver.settlement');
        $receiver->post_code = $this->request->input('receiver.post_code');

        switch ($this->request->input('receiver.pickup')) {
            case 'office':
                $receiver->office_code = Office::find((int)$this->request->input('receiver.office'))->code;
                break;
        }

        return $receiver;
    }


    protected function _shipment()
    {
        $instruction_returns = $this->request->input('shipment.instruction_returns');

        if (!in_array($instruction_returns, [Shipment::RETURNS, Shipment::SHIPPING_RETURNS])) {
            $instruction_returns = null;
        }

        $shipment = new Shipment;

        $shipment->envelope_num = $this->request->input('shipment.num');
        $shipment->shipment_type = $this->request->input('shipment.type');
        $shipment->description = $this->request->input('shipment.description');
        $shipment->pack_count = (int)$this->request->input('shipment.count');
        $shipment->weight = (float)$this->request->input('shipment.weight');
        $shipment->pay_after_accept = (int)!!$this->request->input('shipment.pay_after_accept');
        $shipment->pay_after_test = (int)!!$this->request->input('shipment.pay_after_test');
        $shipment->instruction_returns = $instruction_returns;
        $shipment->invoice_before_pay_CD = (int)!!$this->request->input('shipment.invoice_before_pay');

        $shipment->setTrariffSubCode($this->request->input('sender.pickup'), $this->request->input('receiver.pickup'));

        return $shipment;
    }

    protected function _courier(Shipment &$shipment)
    {
        $date = $this->request->input('courier.date');
        $from = $this->request->input('courier.time_from');
        $to = $this->request->input('courier.time_to');

        if (!$date) {
            return null;
        }

        $from = DateTime::createFromFormat('Y-m-d H:i', "$date $from");
        $to = DateTime::createFromFormat('Y-m-d H:i', "$date $to");

        if (!$from || !$to) {
            return null;
        }

        $courier = new CourierRequest($shipment, $from, $to);

        return $courier;
    }

    protected function _payment()
    {
        $side = Payment::RECEIVER === $this->request->input('payment.side') ? Payment::RECEIVER : Payment::SENDER;
        $method = Payment::COD;
        $key_word = $this->request->input('payment.credit');

        if ($key_word && Payment::SENDER == $side) {
            $method = Payment::CREDIT;
        }

        $payment = new Payment($side, $method, $key_word);

        return $payment;
    }

    protected function _services()
    {
        $dp = $this->request->input('services.dp');
        $cd = (float)$this->request->input('services.cd');
        $oc = (float)$this->request->input('services.oc');
        $oc_currency = $this->request->input('services.oc_currency');
        $cd_currency = $this->request->input('services.cd_currency');
		$cd_agreement_num = $this->request->input('services.cd_agreement_num');

        $services = new Services;
        $services->dp = $dp ? 'ON' : null;

        $services->oc = 0 < $oc && preg_match('#[A-Z]{3}#', $oc_currency) ? $oc : null;
        $services->oc_currency = 0 < $oc && preg_match('#[A-Z]{3}#', $oc_currency) ? $oc_currency : null;

        $services->cd = 0 < $cd && preg_match('#[A-Z]{3}#', $cd_currency) ? $cd : null;
        $services->cd_currency = 0 < $cd && preg_match('#[A-Z]{3}#', $cd_currency) ? $cd_currency : null;
		
        $services->cd_agreement_num = $cd_agreement_num;

        return $services;
    }
}
