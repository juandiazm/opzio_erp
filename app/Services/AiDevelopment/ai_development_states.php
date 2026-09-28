<?php

namespace App\Services\AiDevelopment;

final class ai_development_states
{
    public const CANDIDATE = 'candidate';
    public const AWAITING_APPROVAL = 'awaiting_approval';
    public const REJECTED = 'rejected';
    public const APPROVED = 'approved';
    public const PREPARING = 'preparing';
    public const ANALYZING = 'analyzing';
    public const PLANNING = 'planning';
    public const DEVELOPING = 'developing';
    public const TESTING = 'testing';
    public const FIXING = 'fixing';
    public const INTEGRATING_QA = 'integrating_qa';
    public const WAITING_QA_PIPELINE = 'waiting_qa_pipeline';
    public const WAITING_QUALITY_REVIEW = 'waiting_quality_review';
    public const QUALITY_FEEDBACK = 'quality_feedback';
    public const INTEGRATING_MAIN = 'integrating_main';
    public const WAITING_MAIN_PIPELINE = 'waiting_main_pipeline';
    public const COMPLETED = 'completed';
    public const BLOCKED = 'blocked';
    public const FAILED = 'failed';

    public static function all(): array
    {
        return [
            self::CANDIDATE,
            self::AWAITING_APPROVAL,
            self::REJECTED,
            self::APPROVED,
            self::PREPARING,
            self::ANALYZING,
            self::PLANNING,
            self::DEVELOPING,
            self::TESTING,
            self::FIXING,
            self::INTEGRATING_QA,
            self::WAITING_QA_PIPELINE,
            self::WAITING_QUALITY_REVIEW,
            self::QUALITY_FEEDBACK,
            self::INTEGRATING_MAIN,
            self::WAITING_MAIN_PIPELINE,
            self::COMPLETED,
            self::BLOCKED,
            self::FAILED,
        ];
    }
}