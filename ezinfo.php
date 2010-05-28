<?php
//
// SOFTWARE NAME: eZ Migration Tools
// SOFTWARE RELEASE: 1.0
// COPYRIGHT NOTICE: Jean-Luc Chassaing
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//

class ezmigrationtoolsInfo
{
    static function info()
    {
        $eZCopyrightString = 'Copyright (C) 1999-' . date('Y') . ' Jean-Luc Chassaing';

        return array( 'Name'      => 'eZ Migration Tool',
                      'Version'   => '1.0',
                      'Copyright' => $eZCopyrightString,
                      'License'   => 'GNU General Public License v2.0' );
    }
}

?>
