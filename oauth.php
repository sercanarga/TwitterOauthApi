<?php

class oauth {
    function __construct($c) {
        if (@isset($c['twitter'])) {
            if (@$c['twitter']['enabled'] == 1) {
                if(@$c['twitter']['keys']['key'] != null && @$c['twitter']['keys']['secret'] != null) {
                    new twitter_oauth($c['twitter']['keys']['key'], $c['twitter']['keys']['secret']);
                } else {
                    throw new Exception('Twitter API bilgileri eksik!');
                }
            }
        }
    }
}
?>