<?php

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Psr\Log\LoggerInterface;

class UserPutProcessor implements ProcessorInterface
{
    private ProcessorInterface $persistProcessor;

    private LoggerInterface $logger;

    public function __construct(ProcessorInterface $persistProcessor, LoggerInterface $logger)
    {
        $this->persistProcessor = $persistProcessor;
        $this->logger = $logger;
    }

    /** @param User $data */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        if (!$data->getId()) {
            $this->logger->info(
                sprintf('User %s is being updated', $data->getId()));
        }

        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        return $result;
    }
}
