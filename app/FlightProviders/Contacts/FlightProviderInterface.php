<?php
namespace App\FlightProviders\Contacts;

interface FlightProviderInterface
{
    public function fetch(): array;
    public function getName(): string;
}
