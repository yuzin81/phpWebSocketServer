<?php

trait coreFunc {

    public function Encode($M) {
        // inspiration for Encode() method : 
        // http://stackoverflow.com/questions/8125507/how-can-i-send-and-receive-websocket-messages-on-the-server-side
        $L = strlen($M);
        $bHead = [];
        $bHead[0] = 129; // 0x1 text frame (FIN + opcode)
        if ($L <= 125) {
            $bHead[1] = $L;
        } else if ($L >= 126 && $L <= 65535) {
            $bHead[1] = 126;
            $bHead[2] = ( $L >> 8 ) & 255;
            $bHead[3] = ( $L ) & 255;
        } else {
            $bHead[1] = 127;
            $bHead[2] = ( $L >> 56 ) & 255;
            $bHead[3] = ( $L >> 48 ) & 255;
            $bHead[4] = ( $L >> 40 ) & 255;
            $bHead[5] = ( $L >> 32 ) & 255;
            $bHead[6] = ( $L >> 24 ) & 255;
            $bHead[7] = ( $L >> 16 ) & 255;
            $bHead[8] = ( $L >> 8 ) & 255;
            $bHead[9] = ( $L ) & 255;
        }
        return (implode(array_map("chr", $bHead)) . $M);
    }

    public function Decode($payload) {
        $length = ord($payload[1]) & 127;
        if ($length == 126) {
            $masks = substr($payload, 4, 4);
            $data = substr($payload, 8);
        } else if ($length == 127) {
            $masks = substr($payload, 10, 4);
            $data = substr($payload, 14);
        } else {
            $masks = substr($payload, 2, 4);
            $data = substr($payload, 6, $length); // hgs 30.09.2016
        }
        $text = '';
        for ($i = 0; $i < strlen($data); ++$i) {
            $text .= $data[$i] ^ $masks[$i % 4];
        }
        return $text;
    }

    public function Log($M, $exit = false) {

        if ($this->logToFile) {
            $M = "[" . date(DATE_RFC1036, time()) . "] - $M \r\n";
            file_put_contents($this->logFile, $M, FILE_APPEND);
        }
        if ($this->logToDisplay) {
            $M = "[" . date(DATE_RFC1036, time()) . "] - $M \r\n";
            echo $M;
        }
        if ($exit) {
            exit;
        }
    }

    protected function addClient($Socket) {
        $index = intval($Socket);
        $this->Clients[$index] = (object) ['ID' => $index, 'uuid' => '', 'Headers' => null, 'Handshake' => null, 'timeCreated' => null];
        $this->Sockets[$index] = $Socket;
        return $index;
    }

    protected function getClient($Socket) {
        return $this->Clients[intval($Socket)];
    }

    /*
     * ***********************************************
     * for future use
     * ***********************************************
     */

    private function optAssign($opt) {

        foreach ((object) $this->stdOpt as $key => $defaultValue) {
            if ($opt->{$key}) {
                continue;
            }
            $opt->{$key} = $defaultValue;
        }
        return $opt;
    }

    private function getStdOpt() {
        return (object)
                [
                    'address' => '',
                    'port' => '',
                    'certKey' => '',
                    'certPath' => '',
                    'logFile' => '',
                    'logtoFile' => false,
                    'logToConsol' => true
        ];
    }

}
