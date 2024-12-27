<?php

namespace App\Enums;

/**
 * Псевдо перечисление, так как проект построен на PHP 7.4, а enums доступны только в PHP >= 8.0
 */
final class DocumentType
{
    /**
     * Универсальный передаточный документ (УПД).
     */
    public const UPD = 1;

    /**
     * Счет-фактура.
     */
    public const INVOICE = 2;
}
