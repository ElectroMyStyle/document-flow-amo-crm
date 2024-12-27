<?php

namespace App\Services;

use App\Enums\DocumentType;

/**
 * Сервис фильтрации заметок лидов.
 */
class AmoCrmLeadNotesFilterService
{
    /**
     * Парсит номер документа из строки уведомления.
     *
     * @param string $noteText - Текст уведомления о создании документа.
     * @return int|null - Возвращает номер документа или null.
     */
    public function parseDocumentNumber(string $noteText): ?int
    {
        $docNum = null;
        $beginPos = mb_strpos($noteText, '№');
        if ($beginPos === false)
            return null;
        $beginPos += 1;

        $endPos = mb_strpos($noteText, 'от', $beginPos);
        if ($endPos !== false) {
            $docNum = trim(mb_substr($noteText, $beginPos, ($endPos - $beginPos)));
            $docNum = intval($docNum);
        }

        return $docNum;
    }

    /**
     * Фильтрует заметки всех лидов в массиве.
     *
     * @param array $leads - Массив с лидами.
     * @return array - Возвращает заметки, которые необходимо обработать.
     */
    public function filterNotesInLeads(array $leads): array
    {
        $notes = [];
        foreach ($leads as $lead) {

            foreach ($lead as $note) {

                if (!isset($note['note']['id']) || !isset($note['note']['text']) || !isset($note['note']['note_type']) || !isset($note['note']['metadata'])) {
                    continue;
                }

                if ($note['note']['note_type'] != '25') {
                    continue;
                }

                $jsonNoteMetaData = json_decode($note['note']['metadata'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    continue;
                }
                $note['note']['metadata'] = $jsonNoteMetaData;

                if (!isset($jsonNoteMetaData['event_source']['author_name'])) {
                    continue;
                }

                $jsonNoteMetaData['event_source']['author_name'] = mb_strtolower($jsonNoteMetaData['event_source']['author_name']);
                if (!str_contains($jsonNoteMetaData['event_source']['author_name'], 'интроверт')) {
                    continue;
                }

                $jsonNoteText = json_decode($note['note']['text'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    continue;
                }
                $note['note']['text'] = $jsonNoteText;

                if (!isset($jsonNoteText['service']) || !isset($jsonNoteText['text'])) {
                    continue;
                }

                $jsonNoteText['service'] = mb_strtolower($jsonNoteText['service']);
                $jsonNoteText['text'] = mb_strtolower($jsonNoteText['text']);

                if ($jsonNoteText['service'] !== 'документы' ||
                    (!str_contains($jsonNoteText['text'], 'упд') && !str_contains($jsonNoteText['text'], 'счет-фактура')) ||
                    (!str_contains($jsonNoteText['text'], '№')) ||
                    (!str_contains($jsonNoteText['text'], 'создан'))) {
                    continue;
                }

                $docNum = $this->parseDocumentNumber($jsonNoteText['text']);
                if (empty($docNum))
                    continue;

                $note['note']['doc_num'] = $docNum;
                $note['note']['doc_type_id'] = null;
                if (str_contains($jsonNoteText['text'], 'упд')) {
                    $note['note']['doc_type_id'] = DocumentType::UPD;
                } elseif (str_contains($jsonNoteText['text'], 'счет-фактура')) {
                    $note['note']['doc_type_id'] = DocumentType::INVOICE;
                }

                # Сохраняем нашу заметку о документе
                $notes[] = $note['note'];

            } // foreach ($lead)

        } // foreach ($leads)

        return $notes;
    }
}
