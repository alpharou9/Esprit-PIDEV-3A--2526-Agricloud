<?php

namespace App\Service;

use App\Entity\Event;

class EventTicketContextBuilder
{
    public function buildAvailability(Event $event): array
    {
        $capacity = $event->getCapacity();
        $confirmedCount = $event->getConfirmedCount();
        $remainingSlots = $capacity !== null ? max(0, $capacity - $confirmedCount) : null;
        $fillRatio = $capacity && $capacity > 0 ? $confirmedCount / $capacity : null;

        if ($capacity === null || $capacity <= 0) {
            return [
                'level' => 'open',
                'label' => 'Open registration',
                'color' => '#198754',
                'background' => '#eaf7ef',
                'remainingSlots' => null,
                'capacity' => $capacity,
                'confirmedCount' => $confirmedCount,
                'fillRatio' => null,
            ];
        }

        if ($remainingSlots === 0) {
            return [
                'level' => 'full',
                'label' => 'Full',
                'color' => '#c0392b',
                'background' => '#fdeeed',
                'remainingSlots' => 0,
                'capacity' => $capacity,
                'confirmedCount' => $confirmedCount,
                'fillRatio' => 1.0,
            ];
        }

        if ($fillRatio < 0.5) {
            return [
                'level' => 'available',
                'label' => 'Many spots left',
                'color' => '#198754',
                'background' => '#eaf7ef',
                'remainingSlots' => $remainingSlots,
                'capacity' => $capacity,
                'confirmedCount' => $confirmedCount,
                'fillRatio' => round($fillRatio, 2),
            ];
        }

        if ($fillRatio < 0.85) {
            return [
                'level' => 'filling',
                'label' => 'Filling up',
                'color' => '#d97706',
                'background' => '#fff4df',
                'remainingSlots' => $remainingSlots,
                'capacity' => $capacity,
                'confirmedCount' => $confirmedCount,
                'fillRatio' => round($fillRatio, 2),
            ];
        }

        return [
            'level' => 'almost_full',
            'label' => 'Almost full',
            'color' => '#c0392b',
            'background' => '#fdeeed',
            'remainingSlots' => $remainingSlots,
            'capacity' => $capacity,
            'confirmedCount' => $confirmedCount,
            'fillRatio' => round($fillRatio, 2),
        ];
    }

    public function buildMapSearchUrl(Event $event): ?string
    {
        $location = trim($event->getLocation());
        if ($location === '') {
            return null;
        }

        return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($location);
    }

    public function buildQrCodeUrl(Event $event): ?string
    {
        $mapUrl = $this->buildMapSearchUrl($event);
        if ($mapUrl === null) {
            return null;
        }

        return 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=10&data=' . rawurlencode($mapUrl);
    }
}
