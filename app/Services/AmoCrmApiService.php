<?php

namespace App\Services;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Client\LongLivedAccessToken;
use AmoCRM\Models\CompanyModel;
use AmoCRM\Models\LeadModel;
use Exception;

/**
 * Сервис обёртка над AmoCRMApiClient.
 */
class AmoCrmApiService
{
    /**
     * API клиент AMO CRM.
     *
     * @var AmoCRMApiClient
     */
    protected AmoCRMApiClient $apiClient;

    /**
     * Долгосрочный токен интеграции AMO CRM.
     *
     * @var ?string
     */
    protected ?string $long_lived_access_token;

    /**
     * Конструктор.
     *
     * @param ?string $accountSubdomain - Базовый домен аккаунта AMO CRM.
     * @param ?string $longLivedAccessToken - Долгосрочный токен интеграции AMO CRM.
     * @throws Exception
     */
    public function __construct(?string $accountSubdomain, ?string $longLivedAccessToken)
    {
        $this->long_lived_access_token = $longLivedAccessToken;
        $longLivedAccessToken = new LongLivedAccessToken($this->long_lived_access_token);
        $this->apiClient = new AmoCRMApiClient();
        $this->apiClient->setAccessToken($longLivedAccessToken)->setAccountBaseDomain($accountSubdomain);
    }

    /**
     * Запрашивает информацию о компании через API AMO CRM.
     *
     * @param mixed $companyId - Идентификатор компании.
     * @param int $numberOfAttempts - Кол-во попыток запроса.
     * @return ?CompanyModel
     * @throws Exception
     */
    public function GetCompanyInfo($companyId, int $numberOfAttempts = 1): ?CompanyModel
    {
        if (empty($companyId))
            throw new Exception("Не задан аргумент идентификатор компании");

        $attemptCount = 0;
        $exceptions = [];
        $companyInfo = null;

        do {
            if ($attemptCount > 0)
                sleep(5);

            $attemptCount++;

            try {
                $companyService = $this->apiClient->companies();

                $companyInfo = $companyService->getOne($companyId, [ '' ]);

                if ($companyInfo instanceof CompanyModel)
                    break;
            } catch (Exception $ex) {
                $exceptions[] = $ex;
            }
        } while ($attemptCount <= $numberOfAttempts);

        if (!empty($exceptions))
            throw new $exceptions[0];

        if (empty($companyInfo))
            throw new Exception("Не удалось получить информацию о компании с ID: $companyId");

        return $companyInfo;
    }

    /**
     * Запрашивает информацию о лиде через API AMO CRM.
     *
     * @param mixed $leadId - Идентификатор лида.
     * @param int $numberOfAttempts - Кол-во попыток запроса.
     * @return ?LeadModel
     * @throws Exception
     */
    public function GetLeadInfo($leadId, int $numberOfAttempts = 1): ?LeadModel
    {
        if (empty($leadId))
            throw new Exception("Не задан аргумент идентификатор сделки");

        $attemptCount = 0;
        $exceptions = [];
        $leadInfo = null;

        do {
            if ($attemptCount > 0)
                sleep(5);

            $attemptCount++;

            try {
                $leadService = $this->apiClient->leads();

                $leadInfo = $leadService->getOne($leadId, [ '' ]);

                if ($leadInfo instanceof LeadModel)
                    break;
            } catch (Exception $ex) {
                $exceptions[] = $ex;
            }
        } while ($attemptCount <= $numberOfAttempts);

        if (!empty($exceptions))
            throw new $exceptions[0];

        if (empty($leadInfo))
            throw new Exception("Не удалось получить информацию о лиде с ID: $leadId");

        return $leadInfo;
    }
}
