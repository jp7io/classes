<?php

class Jp7_Interadmin_Exception extends Exception
{
    protected $sql;

    /**
     * Returns $sql.
     *
     * @see Jp7_Interadmin_Exception::$sql
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * Sets $sql.
     *
     * @param object $sql
     *
     * @see Jp7_Interadmin_Exception::$sql
     */
    public function setSql($sql)
    {
        $this->sql = $sql;
    }
}
