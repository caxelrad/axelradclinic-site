<?php

class AxUserMgmtCmds
{

    public static function install_db()
    {
        _ax_debug('AxUserMgmtCmds::install_db()');
        AxelradUserMgmt::db()->sync_tables();
        AxelradUserMgmt::check_default_group();
    }
}