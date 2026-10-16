<?php

namespace App\Enums;

/**
 * The wheel class of a vehicle in the potential-tax file (D-052), from its
 * type: motorcycles, three-wheelers, and four wheels or more.
 */
enum VehicleWheels: string
{
    case Dua = 'r2';
    case Tiga = 'r3';
    case Empat = 'r4';

    /**
     * The class of BPAD's vehicle type, or null when the type is empty.
     */
    public static function fromType(string $type): ?self
    {
        return match (true) {
            $type === '' => null,
            $type === 'SEPEDA MOTOR' => self::Dua,
            str_contains($type, 'R3') => self::Tiga,
            default => self::Empat,
        };
    }
}
