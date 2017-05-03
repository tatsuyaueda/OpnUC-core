<?php

namespace App\Libs;

use PAMI\Client\Impl\ClientImpl;
use PAMI\Message\Event\DialBeginEvent;
use PAMI\Message\Event\DialEndEvent;
use PAMI\Message\Event\EventMessage;
use PAMI\Message\Event\UnknownEvent;
use PAMI\Message\Event\OriginateResponseEvent;

class AsteriskLinker
{

    // クライアント
    private $client = null;
    // 接続状態
    private $connect = false;

    /**
     * AsteriskLinker constructor.
     */
    public function __construct()
    {

        $options = array(
            'host' => config('opnuc.pbx_linker.asterisk.host'),
            'scheme' => 'tcp://',
            'port' => config('opnuc.pbx_linker.asterisk.port'),
            'username' => config('opnuc.pbx_linker.asterisk.username'),
            'secret' => config('opnuc.pbx_linker.asterisk.password'),
            'connect_timeout' => 10,
            'read_timeout' => 20
        );

        $this->client = new ClientImpl($options);

        $this->client->open();

        $this->connect = true;

    }

    /**
     * デストラクタ
     */
    public function __destruct()
    {

        // 接続されている場合は、切断する
        if ($this->connect) {
            $this->client->close();

            $this->connect = false;
        }

    }

    public function processPresence()
    {

        if (!$this->connect) {
            return;
        }

        // すべてのイベントを捕捉
        $this->client->registerEventListener(
            function (EventMessage $event) {
                //var_dump($event);
            }
        );

        // Click 2 Callの結果
        $this->client->registerEventListener(
            function (EventMessage $event) {
                echo $event->getKey('actionid') . ':' . $event->getKey('response') . "\n";
            },
            function (EventMessage $event) {
                return $event instanceof OriginateResponseEvent
                    && $event->getKey('event') == 'OriginateResponse';
            }
        );

        // 着信中
        $this->client->registerEventListener(
            function (EventMessage $event) {
                var_dump($event);
            },
            function (EventMessage $event) {
                return $event instanceof DialBeginEvent
                    && $event->getKey('event') == 'DialBegin';
            }
        );

        // 着信完了
        $this->client->registerEventListener(
            function (EventMessage $event) {
                var_dump($event);
            },
            function (EventMessage $event) {
                return $event instanceof DialEndEvent
                    && $event->getKey('event') == 'DialEnd';
            }
        );


        // Device State
        $this->client->registerEventListener(
            function (EventMessage $event) {
                \Log::debug('AsteriskLinker:DeviceStateChange');
                \Log::debug('  Device:' . $event->getKey('device'));
                \Log::debug('  State:' . $event->getKey('state'));

                // 設定値を正規表現で利用するため、メタ文字をパース
                $prefix = preg_quote(config('opnuc.pbx_linker.asterisk.device_name_prefix'), '/');

                // Device名に数値が含まれているか
                if (!preg_match('/' . $prefix . '(\d+)/', $event->getKey('device'), $matches)) {
                    return;
                }

                // 含まれている場合は、数値を内線番号として扱う
                $ext = $matches[1];
                // 初期ステータス
                $state = 'unknown';

                // ToDo: AsteriskとOpnUCのマッチ必要
                switch ($event->getKey('state')) {
                    case 'INUSE':
                        $state = 'busy';
                        break;
                    case 'RINGING':
                        $state = 'busy';
                        break;
                    case 'NOT_INUSE':
                        $state = 'idle';
                        break;
                    case 'UNAVAILABLE':
                        $state = 'unknown';
                        break;
                    default:
                        echo '$ext ' . $event->getKey('state') . "\n";
                        break;
                }

                // プレゼンスのアップデート
                event(new \App\Events\PresenceUpdated($ext, $state));
            },
            function (EventMessage $event) {
                return $event instanceof UnknownEvent
                    && $event->getKey('event') == 'DeviceStateChange';
            }
        );

        while (true) {
            $this->client->process();
            usleep(1000);
        }

    }

    /**
     * Click2Call
     * @param $ext string 発信元内線番号
     * @param $number string 発信先
     * @return bool
     */
    public function originate($ext, $number)
    {

        $action = new \PAMI\Message\Action\OriginateAction(
            config('opnuc.pbx_linker.asterisk.originate_channel_prefix') . $ext);

        $action->setCallerId(config('opnuc.pbx_linker.asterisk.originate_callerid'));
        //$action->setActionID('1234');
        $action->setContext(config('opnuc.pbx_linker.asterisk.originate_context'));
        $action->setPriority('1');
        $action->setExtension($number);
        // 非同期で発信する
        $action->setAsync(true);

        $result = $this->client->send($action);

        return $result->isSuccess();

    }

}