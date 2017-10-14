<?php

namespace form;

interface etoken_manager_interface
{
    public function get():string;
    public function get_error_message(string $value):string;
}
