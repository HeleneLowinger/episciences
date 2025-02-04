
--
-- Dumping default data for table `MAIL_TEMPLATE`
--

INSERT INTO `MAIL_TEMPLATE` (`PARENTID`, `RVID`, `RVCODE`, `KEY`, `TYPE`, `POSITION`) VALUES
(NULL, NULL, NULL, 'user_registration', 'user', 1),
(NULL, NULL, NULL, 'paper_revision_answer', 'paper_revision', 2),
(NULL, NULL, NULL, 'paper_tmp_version_submitted', 'paper_revision', 3),
(NULL, NULL, NULL, 'paper_new_version_submitted', 'paper_revision', 4),
(NULL, NULL, NULL, 'paper_accepted', 'paper_final_decision', 1),
(NULL, NULL, NULL, 'paper_accepted_tmp_version', 'paper_final_decision', 2),
(NULL, NULL, NULL, 'paper_refused', 'paper_final_decision', 3),
(NULL, NULL, NULL, 'paper_revision_request', 'paper_revision', 1),
(NULL, NULL, NULL, 'paper_comment_author_copy', 'paper_comment', 1),
(NULL, NULL, NULL, 'paper_comment_answer_reviewer_copy', 'paper_comment', 3),
(NULL, NULL, NULL, 'paper_submission_editor_copy', 'paper_submission', 2),
(NULL, NULL, NULL, 'paper_editor_assign', 'paper_editor_assign', 1),
(NULL, NULL, NULL, 'paper_editor_unassign', 'paper_editor_assign', 2),
(NULL, NULL, NULL, 'paper_comment_editor_copy', 'paper_comment', 2),
(NULL, NULL, NULL, 'paper_comment_answer_editor_copy', 'paper_comment', 4),
(NULL, NULL, NULL, 'paper_suggest_acceptation', 'paper_editor_suggestion', 1),
(NULL, NULL, NULL, 'paper_suggest_refusal', 'paper_editor_suggestion', 2),
(NULL, NULL, NULL, 'paper_suggest_new_version', 'paper_editor_suggestion', 3),
(NULL, NULL, NULL, 'user_lost_password', 'user', 3),
(NULL, NULL, NULL, 'paper_submission_author_copy', 'paper_submission', 1),
(NULL, NULL, NULL, 'user_lost_login', 'user', 2),
(NULL, NULL, NULL, 'paper_reviewed_reviewer_copy', 'paper_review', 4),
(NULL, NULL, NULL, 'paper_reviewed_editor_copy', 'paper_review', 5),
(NULL, NULL, NULL, 'paper_deleted_author_copy', 'paper_submission', 3),
(NULL, NULL, NULL, 'paper_deleted_editor_copy', 'paper_submission', 4),
(NULL, NULL, NULL, 'paper_deleted_reviewer_copy', 'paper_submission', 5),
(NULL, NULL, NULL, 'paper_tmp_version_reviewer_reassign', 'paper_review', 7),
(NULL, NULL, NULL, 'paper_new_version_reviewer_reassign', 'paper_review', 6),
(NULL, NULL, NULL, 'paper_reviewer_invitation1', 'paper_review', NULL),
(NULL, NULL, NULL, 'paper_reviewer_invitation2', 'paper_review', NULL),
(NULL, NULL, NULL, 'paper_reviewer_invitation3', 'paper_review', NULL),
(NULL, NULL, NULL, 'paper_reviewer_refusal_reviewer_copy', 'paper_review', NULL),
(NULL, NULL, NULL, 'paper_reviewer_refusal_editor_copy', 'paper_review', NULL),
(NULL, NULL, NULL, 'paper_reviewer_acceptation_reviewer_copy', 'paper_review', NULL),
(NULL, NULL, NULL, 'paper_reviewer_acceptation_editor_copy', 'paper_review', NULL),
(NULL, NULL, NULL, 'reminder_not_enough_reviewers', 'paper_review_reminder', NULL),
(NULL, NULL, NULL, 'reminder_after_revision_deadline_editor_version', 'paper_review_reminder', NULL),
(NULL, NULL, NULL, 'reminder_before_revision_deadline_editor_version', 'paper_review_reminder', NULL),
(NULL, NULL, NULL, 'reminder_after_revision_deadline_author_version', 'paper_review_reminder', NULL),
(NULL, NULL, NULL, 'reminder_before_revision_deadline_author_version', 'paper_review_reminder', NULL),
(NULL, NULL, NULL, 'paper_major_revision_request', 'paper_revision', NULL),
(NULL, NULL, NULL, 'paper_minor_revision_request', 'paper_revision', NULL),
(NULL, NULL, NULL, 'reminder_before_deadline_editor_version', 'paper_review_reminder', 6),
(NULL, NULL, NULL, 'reminder_after_deadline_editor_version', 'paper_review_reminder', 5),
(NULL, NULL, NULL, 'reminder_unanswered_reviewer_invitation_editor_version', 'paper_review_reminder', 4),
(NULL, NULL, NULL, 'paper_updated_rating_deadline', 'paper_review', NULL),
(NULL, NULL, NULL, 'reminder_after_deadline_reviewer_version', 'paper_review_reminder', 3),
(NULL, NULL, NULL, 'reminder_before_deadline_reviewer_version', 'paper_review_reminder', 2),
(NULL, NULL, NULL, 'reminder_unanswered_reviewer_invitation_reviewer_version', 'paper_review_reminder', 1),
(NULL, NULL, NULL, 'paper_reviewer_removal', 'paper_review', NULL),
(NULL, NULL, NULL, 'paper_published_author_copy', 'paper_final_decision', NULL),
(NULL, NULL, NULL, 'paper_published_editor_copy', 'paper_final_decision', NULL),
(NULL, NULL, NULL, 'paper_ask_other_editors', 'paper_final_decision', NULL),
(NULL, NULL, NULL, 'paper_new_version_reviewer_reinvitation', 'paper_review', NULL),
(NULL, NULL, NULL, 'paper_reviewer_paper_accepted_stop_pending_reviewing', 'paper_review', 8),
(NULL, NULL, NULL, 'paper_reviewer_paper_revision_request_stop_pending_reviewing', 'paper_review', 9),
(NULL, NULL, NULL, 'paper_accepted_editors_copy', 'paper_final_decision', 2),
(NULL, NULL, NULL, 'paper_reviewer_paper_refused_stop_pending_reviewing', 'paper_review', 10),
(NULL, NULL, NULL, 'paper_reviewer_paper_published_stop_pending_reviewing', 'paper_review', 11),
(NULL, NULL, NULL, 'paper_editor_refused_monitoring', 'paper_editor_assign', 3),
(NULL, NULL, NULL, 'paper_abandon_publication_author_copy', 'abandon_publication_process', NULL),
(NULL, NULL, NULL, 'paper_abandon_publication_editor_copy', 'abandon_publication_process', NULL),
(NULL, NULL, NULL, 'paper_continue_publication_author_copy', 'continue_publication_process', NULL),
(NULL, NULL, NULL, 'paper_continue_publication_editor_copy', 'continue_publication_process', NULL),
(NULL, NULL, NULL, 'paper_abandon_publication_reviewer_removal', 'abandon_publication_process', 3),
(NULL, NULL, NULL, 'paper_copyeditor_assign', 'paper_copy_editing', 1),
(NULL, NULL, NULL, 'paper_copyeditor_unassign', 'paper_copy_editing', 4),
(NULL, NULL, NULL, 'paper_copyeditor_assign_author_copy', 'paper_copy_editing', 3),
(NULL, NULL, NULL, 'paper_copyeditor_assign_editor_copy', 'paper_copy_editing', 2),
(NULL, NULL, NULL, 'paper_ce_waiting_for_author_sources_editor_copy', 'paper_copy_editing', 5),
(NULL, NULL, NULL, 'paper_ce_waiting_for_author_sources_author_copy', 'paper_copy_editing', 6),
(NULL, NULL, NULL, 'paper_comment_by_editor_editor_copy', 'paper_comment', 5),
(NULL, NULL, NULL, 'paper_ce_author_sources_deposed_response_copyeditors_and_editors_copy', 'paper_copy_editing', 7),
(NULL, NULL, NULL, 'paper_ce_author_sources_deposed_response_author_copy', 'paper_copy_editing', 8),
(NULL, NULL, NULL, 'paper_ce_waiting_for_author_formatting_editor_and_copyeditor_copy', 'paper_copy_editing', 9),
(NULL, NULL, NULL, 'paper_ce_waiting_for_author_formatting_author_copy', 'paper_copy_editing', 10),
(NULL, NULL, NULL, 'paper_ce_author_vesrion_finale_deposed_editor_and_copyeditor_copy', 'paper_copy_editing', 11),
(NULL, NULL, NULL, 'paper_ce_author_vesrion_finale_deposed_author_copy', 'paper_copy_editing', 12),
(NULL, NULL, NULL, 'paper_ce_accepted_final_version_author_copy', 'paper_copy_editing', 14),
(NULL, NULL, NULL, 'paper_ce_accepted_final_version_copyeditor_and_editor_copy', 'paper_copy_editing', 13),
(NULL, NULL, NULL, 'paper_ce_review_formatting_deposed_author_copy', 'paper_copy_editing', 16),
(NULL, NULL, NULL, 'paper_ce_review_formatting_deposed_editor_and_copyeditor_copy', 'paper_copy_editing', 15),
(NULL, NULL, NULL, 'paper_abandon_publication_by_author_author_copy', 'abandon_publication_process', NULL),
(NULL, NULL, NULL, 'paper_abandon_publication_no_assigned_editors', 'abandon_publication_process', NULL),
(NULL, NULL, NULL, 'paper_section_editor_assign', 'paper_editor_assign', 1),
(NULL, NULL, NULL, 'paper_volume_editor_assign', 'paper_editor_assign', 1),
(NULL, NULL, NULL, 'paper_suggested_editor_assign', 'paper_editor_assign', 1),
(NULL, NULL, NULL, 'paper_submission_updated_editor_copy', 'paper_submission', 6),
(NULL, NULL, NULL, 'paper_submission_updated_author_copy', 'paper_submission', 7),
(NULL, NULL, NULL, 'reminder_article_blocked_in_accepted_state_editor_version', 'paper_review_reminder', NULL),
(NULL, NULL, NULL, 'paper_refused_editors_copy', 'paper_final_decision', 3),
(NULL, NULL, NULL, 'paper_submission_other_recipient_copy', 'paper_submission', 8),
(NULL, NULL, NULL, 'paper_accepted_tmp_version_managers_copy', 'paper_final_decision', 2);


