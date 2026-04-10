<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Auth\Resolvers;

use PTAdmin\Admin\Services\Auth\AuthorizationContext;
use PTAdmin\Admin\Services\Auth\AuthorizationDecision;
use PTAdmin\Contracts\Auth\AuthorizationResolverInterface;
use PTAdmin\Contracts\Auth\CapabilityServiceInterface;
use PTAdmin\Contracts\Auth\WorkflowServiceInterface;
use PTAdmin\Support\Enums\GrantEffect;

class WorkflowGuardResolver implements AuthorizationResolverInterface
{
    public function supports(AuthorizationContext $context): bool
    {
        return app(CapabilityServiceInterface::class)->enabled('workflow')
            && app(WorkflowServiceInterface::class)->hasSignals($context->workflowMetadata());
    }

    public function resolve($subject, string $ability, string $resourceCode, AuthorizationContext $context, AuthorizationDecision $decision): AuthorizationDecision
    {
        if (!$decision->allowed || null === $decision->effect) {
            return $decision;
        }

        $workflow = $context->workflowMetadata();
        $workflowService = app(WorkflowServiceInterface::class);
        $requestedAction = $workflowService->requestedAction($workflow, $ability);

        if (($workflow['locked'] ?? false) && !\in_array($requestedAction, ['view', 'access'], true)) {
            $decision->allowed = false;
            $decision->effect = GrantEffect::DENY;
            $decision->reason = 'Workflow is locked for current action.';

            return $decision;
        }

        $allowedActions = (array) ($workflow['allowed_actions'] ?? []);
        if ([] !== $allowedActions && !\in_array($requestedAction, $allowedActions, true)) {
            $decision->allowed = false;
            $decision->effect = GrantEffect::DENY;
            $decision->reason = sprintf('Workflow action [%s] is not allowed.', $requestedAction);

            return $decision;
        }

        $assigneeIds = (array) ($workflow['assignee_ids'] ?? []);
        if ([] === $assigneeIds) {
            return $decision;
        }

        if (!$workflowService->assignedToSubject($workflow, $subject)) {
            $decision->allowed = false;
            $decision->effect = GrantEffect::DENY;
            $decision->reason = 'Workflow action is not assigned to current user.';
        }

        return $decision;
    }
}
