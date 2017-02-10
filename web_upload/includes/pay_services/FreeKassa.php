<?php
/**************************************************************************
 * Эта программа является частью SourceBans MATERIAL Admin.
 *
 * Все права защищены © 2016-2017 Sergey Gut <webmaster@kruzefag.ru>
 *
 * SourceBans MATERIAL Admin распространяется под лицензией
 * Creative Commons Attribution-NonCommercial-ShareAlike 3.0.
 *
 * Вы должны были получить копию лицензии вместе с этой работой. Если нет,
 * см. <http://creativecommons.org/licenses/by-nc-sa/3.0/>.
 *
 * ПРОГРАММНОЕ ОБЕСПЕЧЕНИЕ ПРЕДОСТАВЛЯЕТСЯ «КАК ЕСТЬ», БЕЗ КАКИХ-ЛИБО
 * ГАРАНТИЙ, ЯВНЫХ ИЛИ ПОДРАЗУМЕВАЕМЫХ, ВКЛЮЧАЯ, НО НЕ ОГРАНИЧИВАЯСЬ,
 * ГАРАНТИИ ПРИГОДНОСТИ ДЛЯ КОНКРЕТНЫХ ЦЕЛЕЙ И НЕНАРУШЕНИЯ. НИ ПРИ КАКИХ
 * ОБСТОЯТЕЛЬСТВАХ АВТОРЫ ИЛИ ПРАВООБЛАДАТЕЛИ НЕ НЕСУТ ОТВЕТСТВЕННОСТИ ЗА
 * ЛЮБЫЕ ПРЕТЕНЗИИ, ИЛИ УБЫТКИ, НЕЗАВИСИМО ОТ ДЕЙСТВИЯ ДОГОВОРА,
 * ГРАЖДАНСКОГО ПРАВОНАРУШЕНИЯ ИЛИ ИНАЧЕ, ВОЗНИКАЮЩИЕ ИЗ, ИЛИ В СВЯЗИ С
 * ПРОГРАММНЫМ ОБЕСПЕЧЕНИЕМ ИЛИ ИСПОЛЬЗОВАНИЕМ ИЛИ ИНЫМИ ДЕЙСТВИЯМИ
 * ПРОГРАММНОГО ОБЕСПЕЧЕНИЯ.
 *
 * Эта программа базируется на работе, охватываемой следующим авторским
 *                                                           правом (ами):
 *
 *  * SourceBans ++
 *    Copyright © 2014-2016 Sarabveer Singh
 *    Выпущено под лицензией CC BY-NC-SA 3.0
 *    Страница: <https://sbpp.github.io/>
 *
 ***************************************************************************/

if (!defined('IN_SB')) {echo("Вы не должны быть здесь. Используйте только ссылки внутри системы!");die();}
if (!class_exists('CPaymentService')) require_once(INCLUDES_PATH . '/CDonate.php');

class FreeKassa extends CPaymentService {
    private $billing;
    
    public function __construct($bill) {
        $this->billing = $bill;
        $this->billing->register_event('onPaymentSuccessful', [$this, 'onPayment']);
    }
    
    public function onPayment($service, $id) {
        
    }

    public function getName() {
        return 'FreeKassa';
    }
    
    public function getAuthor() {
        return '<a href="https://steamcommunity.com/profiles/76561198071596952/" target="_blank">CrazyHackGUT</a>';
    }
    
    public function getVersion() {
        return '0.1-dev';
    }
    
    public function getUrl() {
        return 'https://www.free-kassa.ru/';
    }
    
    public function getClientSign() {
        $order_id   = (int) func_get_arg(0);
        $summ       = (int) func_get_arg(1);
        return md5(sprintf("%s:%d:%s:%d", $GLOBALS['config']['billing.freekassa.shop_id'], $summ, $GLOBALS['config']['billing.freekassa.secret_word.client'], $order_id));
    }
    
    public function getNotifySign() {
        $order_id   = (int) func_get_arg(0);
        $summ       = (int) func_get_arg(1);
        return md5(sprintf("%s:%d:%s:%d", $GLOBALS['config']['billing.freekassa.shop_id'], $summ, $GLOBALS['config']['billing.freekassa.secret_word.notify'], $order_id));
    }
    
    public function generatePaymentUrl() {
        $m          = (int) $GLOBALS['config']['billing.freekassa.shop_id'];
        $oa         = (int) func_get_arg(0);
        $o          = (int) func_get_arg(1);
        $sign       = $this->getNotifySign($o, $oa);
        
        return sprintf("http://www.free-kassa.ru/merchant/cash.php?m=%d&oa=%d&o=%d&s=%s", $m, $oa, $o, $sign);
    }
}
