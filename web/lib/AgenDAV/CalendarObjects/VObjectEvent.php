<?php 
namespace AgenDAV\CalendarObjects;

use \Sabre\VObject;

/*
 * Copyright 2013 Jorge López Pérez <jorge@adobo.org>
 *
 *  This file is part of AgenDAV.
 *
 *  AgenDAV is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  any later version.
 *
 *  AgenDAV is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with AgenDAV.  If not, see <http://www.gnu.org/licenses/>.
 */

class VObjectEvent implements IEvent
{
    private $href;

    private $etag;

    private $vevent;

    public static $interesting_properties = array(
        'DURATION' => 'duration',
        'SUMMARY' => 'title',
        'UID' => 'uid',
        'DESCRIPTION' => 'description',
        'RRULE' => 'rrule',
        'RECURRENCE-ID' => 'recurrence-id',
        'LOCATION' => 'location',
        'TRANSP' => 'transp',
        'CLASS' => 'icalendar_class',
    );

    public function __construct($vevent = null, $href = null, $etag = null)
    {
        if ($vevent !== null) {
            $this->vevent = $vevent;
            $this->href = $href;
            $this->etag = $etag;
        } else {
            $vevent = VObject\Component::create('VEVENT');
            $this->href = null;
            $this->etag = null;
        }
    }

    public function getHref()
    {
        return $this->href;
    }

    public function getEtag()
    {
        return $this->etag;
    }

    public function getVEVENT()
    {
        return $this->vevent;
    }

    public function setHref($href)
    {
        $this->href = $href;
    }

    public function setEtag($etag)
    {
        $this->etag = $etag;
    }

    public function setVEVENT($vevent)
    {
        $this->vevent = $vevent;
    }

    public function toArray()
    {
        if ($this->vevent === null) {
            throw new \UnexpectedValueException('Null VEVENT');
        }

        $result = array(
            'href' => $this->href,
            'etag' => $this->etag,
        );

        // Start and end dates
        $start = $this->vevent->DTSTART->getDateTime();
        $end = $this->vevent->DTEND;

        if ($end === null) {
            // Event is lacking DTEND
            $end = clone $start;

            if ($this->vevent->DURATION !== null) {
                $end->add(VObject\DateTimeParser::parseDuration($this->vevent->DURATION));
            } elseif ($this->vevent->DTSTART->getDateType() == VObject\Property\DateTime::DATE) {
                $end->modify('+1 day');
            }
        } else {
            $end = $end->getDateTime();
        }

        // All day events
        $result['allDay'] = false;
        if ($this->vevent->DTSTART->getDateType() == VObject\Property\DateTime::DATE) {
            $result['allDay'] = true;
        } elseif ($start->format('Hi') == '0000' && $start->diff($end)->format('s') == '86400') {
            $result['allDay'] = true;
        }

        foreach (self::$interesting_properties as $name => $index) {
            $property = $this->vevent->{$name};

            if ($property === null) {
                continue;
            }

            switch ($name) {
                case 'SUMMARY':
                case 'UID':
                case 'LOCATION':
                case 'CLASS':
                case 'TRANSP':
                case 'RECURRENCE-ID':
                    $result[$index] = $property->value;
                    break;
                case 'DURATION':
                    $result[$index] = '';
                    break;
            }
        }

        // Reminders TODO

        $result['start'] = $start->format(\DateTime::ISO8601);
        $result['end'] = $end->format(\DateTime::ISO8601);
        return $result;
    }

}
