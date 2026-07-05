import { LucideIcon } from 'lucide-react';
import type { Language, Exam, PricingPlan } from '@/data/languages';

export type { Language, Exam, PricingPlan };

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    userProfile?: UserProfile | null;
    isPremium: boolean;
    onTrial: boolean;
    trialDaysLeft: number;
    freeExercisesUsedToday: number;
    freeExercisesLimit: number;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    profile?: UserProfile | null;
    [key: string]: unknown;
}

export interface UserProfile {
    id: number;
    user_id: number;
    target_exam_id: number | null;
    target_score: number | null;
    exam_date: string | null;
    current_level: string | null;
    xp_total: number;
    streak_current: number;
    streak_last_date: string | null;
    onboarding_completed_at: string | null;
    trial_ends_at: string | null;
    target_exam?: ExamWithLanguage | null;
}

export interface LanguageWithExams {
    id: number;
    slug: string;
    name: string;
    native_name: string;
    flag: string;
    is_active: boolean;
    exams: ExamRecord[];
}

export interface ExamRecord {
    id: number;
    language_id: number;
    slug: string;
    name: string;
    max_score: number | null;
    scoring_type: string;
    levels: string[] | null;
    language?: LanguageWithExams;
    sections?: ExamSection[];
}

export interface ExamWithLanguage extends ExamRecord {
    language: LanguageWithExams;
}

export interface ExamSection {
    id: number;
    exam_id: number;
    slug: string;
    name: string;
    skill_type: 'reading' | 'listening' | 'writing' | 'speaking';
    time_limit: number | null;
    sort_order: number;
    exercise_types?: ExerciseTypeRecord[];
}

export interface ExerciseTypeRecord {
    id: number;
    section_id: number;
    slug: string;
    name: string;
    skill_type: 'reading' | 'listening' | 'writing' | 'speaking';
    component_key: string;
}

export interface ExerciseRecord {
    id: number;
    exercise_type_id: number;
    exam_id: number;
    content: Record<string, unknown>;
    questions: QuestionItem[];
    difficulty: string;
    xp_reward: number;
    is_ai_generated: boolean;
    exercise_type?: ExerciseTypeRecord;
    exam?: ExamRecord;
}

export interface QuestionItem {
    id: string;
    type: string;
    text: string;
    options?: string[];
    correct_answer: string | string[];
    explanation?: string;
    audio_url?: string | null;
    audio_text?: string | null;
    passage?: string | null;
    [key: string]: unknown;
}

export interface ExerciseAttempt {
    id: number;
    user_id: number;
    exercise_id: number;
    answers: Record<string, unknown>;
    score: number | null;
    accuracy_percent: number | null;
    time_spent: number | null;
    xp_earned: number;
    feedback: Record<string, unknown> | null;
    created_at: string;
    exercise?: ExerciseRecord;
}

export interface DailyTask {
    id: string;
    title: string;
    type: string;
    completed: boolean;
    xp_reward: number;
}

export interface LeaderboardEntry {
    id: number;
    user_id: number;
    period_type: string;
    period_key: string;
    xp: number;
    rank: number | null;
    user?: User;
}

export interface AchievementRecord {
    id: number;
    slug: string;
    name: string;
    description: string;
    icon: string | null;
    xp_reward: number;
    condition_type: string;
    condition_value: Record<string, unknown>;
    pivot?: { earned_at: string };
}

export interface LearningPathNode {
    id: number;
    exam_id: number;
    sort_order: number;
    title: string;
    node_type: 'lesson' | 'practice' | 'boss';
    exercise_ids: number[] | null;
    progress?: UserLearningProgress;
}

export interface UserLearningProgress {
    status: 'locked' | 'available' | 'in_progress' | 'completed';
}
