<?php

class Episciences_Paper_ConflictsManager
{
    /**
     * @param int $paperId
     * @return array [Episciences_Paper_Conflict]
     */
    public static function findByPaperId(int $paperId): array
    {

        $oResult = [];
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()
            ->from(T_PAPER_CONFLICTS)
            ->where('paper_id = ?', $paperId);

        $rows = $db->fetchAssoc($sql);

        foreach ($rows as $value) {
            $oResult[] = new Episciences_Paper_Conflict($value);
        }

        return $oResult;
    }

    /**
     * @param int $uid
     * @param string|null $answer
     * @return array [Episciences_Paper_Conflict]
     */
    public static function findByUidAndAnswer(int $uid, string $answer = null): array
    {
        $oConflicts = [];

        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        $sql = $db->select()
            ->from(T_PAPER_CONFLICTS)
            ->where("`by` = ?", $uid);

        if ($answer) {
            $sql->where('answer = ?', $answer);
        }

        $rows = $db->fetchAll($sql);

        foreach ($rows as $row) {
            $oConflicts [] = new Episciences_Paper_Conflict($row);
        }

        return $oConflicts;
    }

    /**
     * @return array [Episciences_Paper_Conflict]
     */
    public static function all(): array
    {
        $conflicts = [];
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        $sql = $db->select()->from(T_PAPER_CONFLICTS);

        $rows = $db->fetchAll($sql);

        foreach ($rows as $row) {

            $oConflict = new Episciences_Paper_Conflict($row);

            $conflicts [$oConflict->getCid()] = $oConflict;

        }

        return $conflicts;
    }


    /**
     * @param int $uid
     * @param int $paperId
     * @return bool
     */
    public static function deleteByUidAndPaperId(int $uid, int $paperId): bool
    {
        if ($paperId < 1) {
            return false;
        }

        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        return ($db->delete(T_PAPER_CONFLICTS, ['paper_id = ?' => $paperId, 'by' => $uid]) > 0);

    }

    /**
     * @param int $id
     * @return bool
     */
    public static function deleteById(int $id): bool
    {
        if ($id < 1) {
            return false;
        }

        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        return ($db->delete(T_PAPER_CONFLICTS, ['cid = ?' => $id]) > 0);

    }

}