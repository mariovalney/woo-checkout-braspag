<?php
namespace Braspag\API;

interface BraspagSerializable extends \JsonSerializable
{
    public function populate(\stdClass $data);
}