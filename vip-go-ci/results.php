<?php

/*
 * Remove comments that exist on a GitHub Pull-Request from
 * the results array. Will loop through each Pull-Request
 * affected by the current commit, and remove any comment
 * from the results array if it already exists.
 */
function vipgoci_results_remove_existing_github_comments(
	$options,
	$prs_implicated,
	&$results,
	$repost_comments_from_dismissed_reviews = false,
	$prs_events_dismissed_by_team = array()
) {
	vipgoci_log(
		'Removing existing GitHub comments from results' .
			' to be posted to GitHub API',
		array(
			'repo_owner' => $options['repo-owner'],
			'repo_name' => $options['repo-name'],
			'prs_implicated' => array_keys( $prs_implicated ),
			'repost_comments_from_dismissed_reviews' => $repost_comments_from_dismissed_reviews,
			'prs_events_dismissed_by_team' => $prs_events_dismissed_by_team,
		)
	);

	$comments_removed = array();

	foreach ( $prs_implicated as $pr_item ) {
		$prs_comments = array();

		if ( ! isset(
			$comments_removed[ $pr_item->number ]
		) ) {
			$comments_removed[ $pr_item->number ] = array();
		}

		/*
		 * Get all commits related to the current
		 * Pull-Request.
		 */

		$pr_item_commits = vipgoci_github_prs_commits_list(
			$options['repo-owner'],
			$options['repo-name'],
			$pr_item->number,
			$options['token']
		);

		/*
		 * Loop through each commit, fetching all comments
		 * made in relation to that commit
		 */

		foreach ( $pr_item_commits as $pr_item_commit_id ) {
			vipgoci_github_pr_reviews_comments_get(
				$options,
				$pr_item_commit_id,
				$pr_item->created_at,
				$prs_comments // pointer used
			);

			unset( $pr_item_commit_id );
		}


		/*
		 * Ignore dismissed reviews, if requested.
		 */
		if ( true === $repost_comments_from_dismissed_reviews ) {
			vipgoci_log(
				'Later on, will make sure comments ' .
					'that are part of dismissed reviews ' .
					'will be submitted again, if the ' .
					'underlying issue was detected ' .
					'during the run. In case of such a setting' .
					'and such reviews existing, excluding ' .
					'reviews (and thus comments) that are submitted ' .
					'by members of a particular team ' .
					'from this process',
				array(
					'teams' =>
						$options['dismissed-reviews-exclude-reviews-from-team'],

					'pr_number' =>
						$pr_item->number,
				)
			);

			/*
			 * Get dismissed reviews submitted by us
			 * and extract ID of each.
			 */
			$pr_reviews = vipgoci_github_pr_reviews_get(
				$options['repo-owner'],
				$options['repo-name'],
				$pr_item->number,
				$options['token'],
				array(
					'login' => 'myself',
					'state' => array( 'DISMISSED' )
				)
			);

			$dismissed_reviews = array_column(
				$pr_reviews,
				'id'
			);

			unset( $pr_reviews );

			/*
			 * Some reviews (and comments) should not be posted,
			 * again, as per setting determined by our caller;
			 * honor this here.
			 */
			if ( ! empty(
				$prs_events_dismissed_by_team[
					$pr_item->number
				]
			) ) {

				$all_review_ids = $dismissed_reviews;

				$dismissed_reviews = array_diff(
					$all_review_ids,
					$prs_events_dismissed_by_team[
						$pr_item->number
					]
				);

				vipgoci_log(
					'Excluding certain reviews from ' .
						'list of previously gathered dismissed reviews; ' .
						'will only keep reviews that were not dismissed by ' .
						'members of a particular team. The comments of ' .
						'the outstanding, kept, reviews might be posted again ' .
						'if the underlying issue was detected',
					array(
						'prs_events_dismissed_by_team_and_pr' =>
							$prs_events_dismissed_by_team[
								$pr_item->number
							],

						'all_review_ids' =>
							$all_review_ids,

						'dismissed_reviews' =>
							$dismissed_reviews,
					)
				);

				unset( $all_review_ids );
			}


			/*
			 * Loop through each file to have comments
			 * submitted against, then look through each
			 * comment, looking for any comment associated
			 * with dismissed reviews.
			 *
			 * If we find a dismissed review, we will act
			 * as if the comment was never there by removing
			 * it from $prs_comments. This will ensure
			 * that our to-be posted review will contain
			 * such comments, even though they could be
			 * considered duplictes. The aim is to make
			 * them more visible and part of a blocking review.
			 *
			 * Note that some comments might be excluded
			 * from this, as per above.
			 */

			$removed_comments = array();

			foreach(
				$prs_comments as
					$pr_comment_key => $pr_comments_items
			) {
				foreach(
					$pr_comments_items as
					$pr_review_key => $pr_review_comment
				) {
					if ( false === in_array(
						$pr_review_comment->pull_request_review_id,
						$dismissed_reviews
					) ) {
						continue;
					}

					$removed_comments[] = array(
						'pr_number' =>
							$pr_item->number,

						'pull_request_review_id' =>
							$pr_review_comment->pull_request_review_id,

						'comment_id' =>
							$pr_review_comment->id,

						'message_body' =>
							$pr_review_comment->body,

						'message_created_at' =>
							$pr_review_comment->created_at,

						'message_updated_at' =>
							$pr_review_comment->updated_at,
					);


					/*
					 * Comment is a part of a dismissed review
					 * (that was not excluded), now get
					 * rid of the comment -- act as if was
					 * never there.
					 */
					unset(
						$prs_comments[
							$pr_comment_key
						][
							$pr_review_key
						]
					);
				}
			}

			vipgoci_log(
				'Removed following comments from list of previously submitted ' .
					'comments to older PR reviews, as they are ' .
					'part of dismissed reviews. Note that some ' .
					'dismissed reviews might have been excluded previously',

				array(
					'removed_comments' =>
						$removed_comments,
				)
			);

			unset( $removed_comments );
			unset( $dismissed_reviews );
		}


		foreach(
			$results['issues'][ $pr_item->number ] as
				$tobe_submitted_cmt_key =>
					$tobe_submitted_cmt
		) {

			/*
			 * Filter out issues that have already been
			 * reported to GitHub.
			 */

			if (
				// Only do check if everything above is looking good
				vipgoci_github_comment_match(
					$tobe_submitted_cmt['file_name'],
					$tobe_submitted_cmt['file_line'],
					$tobe_submitted_cmt['issue']['message'],
					$prs_comments
				)
			) {
				/*
				 * Keep a record of what we remove.
				 */
				$comments_removed[ $pr_item->number ][] =
					$tobe_submitted_cmt;

				/* Remove it */
				unset(
					$results[
						'issues'
					][
						$pr_item->number
					][
						$tobe_submitted_cmt_key
					]
				);

				/*
				 * Update statistics
				 */
				$results[
					'stats'
				][
					$tobe_submitted_cmt['type']
				][
					$pr_item->number
				][
					strtolower(
						$tobe_submitted_cmt['issue']['type']
					)
				]--;
			}
		}

		/*
		 * Re-create the issues
		 * array, so that no array
		 * keys are missing.
		 */
		$results[
			'issues'
		][
			$pr_item->number
		] = array_values(
			$results[
				'issues'
			][
				$pr_item->number
			]
		);
	}

	/*
	 * Report what we removed.
	 */
	vipgoci_log(
		'Removed following comments from array of ' .
		'to be submitted comments to PRs, as they ' .
		'have been submitted already',
		array(
			'comments_removed' => $comments_removed
		)
	);
}


/*
 * For each approved file, remove any issues
 * to be submitted against them. However,
 * do not do this for 'info' type messages,
 * as they are informational, and not problems.
 *
 * We do this, because sometimes Pull-Requests
 * will be opened that contain approved code,
 * and we do not want to clutter them with
 * non-relevant comments.
 *
 * Make sure to update statistics to
 * reflect this.
 */

function vipgoci_results_approved_files_comments_remove(
	$options,
	&$results,
	$auto_approved_files_arr
) {

	$issues_removed = array(
	);

	vipgoci_log(
		'Removing any potential issues (errors, warnings) ' .
			'found for approved files from internal results',

		array(
			'auto_approved_files_arr' => $auto_approved_files_arr,
		)
	);

	/*
 	 * Loop through each Pull-Request
	 */
	foreach( $results['issues'] as
		$pr_number => $pr_issues
	) {
		/*
		 * Loop through each issue affecting each
		 * Pull-Request.
		 */
		foreach( $pr_issues as
			$issue_number => $issue_item
		) {

			/*
			 * If the file affected is
			 * not found in the auto-approved files,
			 * do not to anything.
			 */
			if ( ! isset(
				$auto_approved_files_arr[
					$issue_item['file_name']
				]
			) ) {
				continue;
			}

			/*
			 * We do not touch on 'info' type,
			 * as that does not report any errors.
			 */

			if ( strtolower(
				$issue_item['issue']['type']
			) === 'info' ) {
				continue;
			}

			/*
			 * We have found an item that is approved,
			 * and has non-info issues -- remove it
			 * from the array of submittable issues.
			 */
			unset(
				$results[
					'issues'
				][
					$pr_number
				][
					$issue_number
				]
			);

			/*
			 * Update statistics accordingly.
			 */
			$results[
				'stats'
			][
				$issue_item['type']
			][
				$pr_number
			][
				strtolower(
					$issue_item['issue']['type']
				)
			]--;

			/*
			 * Update our own information array on
			 * what we did.
			 */
			$issues_removed[
				$pr_number
			][] = $issue_item;
		}

		/*
		 * Re-order the array as
		 * some keys might be missing
		 */
		$results[
			'issues'
		][
			$pr_number
		] = array_values(
			$results[
				'issues'
			][
				$pr_number
			]
		);
	}


	vipgoci_log(
		'Completed cleaning out issues for pre-approved files',
		array(
			'issues_removed' => $issues_removed,
		)
	);
}

/*
 * Limit the number of to-be-submitted comments to
 * the Pull-Requests. We take into account the number
 * to be submitted for each Pull-Request, the number of
 * comments already submitted, and the limit specified
 * on start-up. Comments are removed as needed, and
 * what comments are removed is reported.
 */
function vipgoci_results_filter_comments_to_max(
	$options,
	&$results,
	&$prs_comments_maxed
) {

	vipgoci_log(
		'Preparing to remove any excessive number comments from array of ' .
			'issues to be submitted to PRs',
		array(
			'review_comments_total_max'
				=> $options['review-comments-total-max'],
		)
	);


	/*
	 * We might need to remove comments.
	 *
	 * We will begin with lower priority comments
	 * first, remove them, and then progressively
	 * continue removing comments as priority increases
	 * and there is still a need for removal.
	 */

	/*
	 * Keep track of what we remove.
	 */
	$comments_removed = array();

	foreach(
		$results['issues'] as
			$pr_number => $pr_issues_comments
	) {
		/*
		 * Take into account previously submitted comments
		 * by us for the current Pull-Request.
		 */

		$pr_previous_comments_cnt = count(
			vipgoci_github_pr_reviews_comments_get_by_pr(
				$options,
				$pr_number,
				array(
					'login'			=> 'myself',
					'comments_active'	=> true,
				)
			)
		);

		/*
		 * How many comments need
		 * to be removed? Count in
		 * comments in the PR in addition
		 * to possible new ones, substract
		 * from the maximum specified.
		 */

		$comments_to_remove =
			(
				count( $pr_issues_comments )
				+
				$pr_previous_comments_cnt
			)
			-
			$options['review-comments-total-max'];

		/*
		 * If there are no comments to remove,
		 * skip and continue.
		 */
		if ( $comments_to_remove <= 0 ) {
			continue;
		}

		/*
		 * If more are to be removed than are to be
		 * submitted, limit to the number of available ones.
		 */
		else if (
			$comments_to_remove >
				count( $pr_issues_comments )
		) {
			$comments_to_remove = count( $pr_issues_comments );
		}

		/*
		 * Figure out severity, minimum and maximum.
		 */

		$severity_min = 0;
		$severity_max = 0;

		foreach( $pr_issues_comments as $pr_issue ) {
			$severity_min = min(
				$pr_issue['issue']['severity'],
				$severity_min
			);

			$severity_max = max(
				$pr_issue['issue']['severity'],
				$severity_max
			);
		}

		/*
		 * Loop through severity-levels from low to high
		 * and remove comments as needed.
		 */
		for (
			$severity_current = $severity_min;
			$severity_current <= $severity_max &&
				$comments_to_remove > 0;
			$severity_current++
		) {
			foreach(
				$pr_issues_comments as
					$pr_issue_key => $pr_issue
			) {
				/*
				 * If we have removed enough, stop here.
				 */
				if ( $comments_to_remove <= 0 ) {
					break;
				}

				/*
				 * Not correct severity level? Ignore.
				 */
				if (
					$pr_issue['issue']['severity'] !==
					$severity_current
				) {
					continue;
				}

				/*
				 * Actually remove and
				 * keep statistics up to date.
				 */

				unset(
					$results[
						'issues'
					][
						$pr_number
					][
						$pr_issue_key
					]
				);

				$results[
					'stats'
				][
					$pr_issue['type']
				][
					$pr_number
				][
					strtolower(
						$pr_issue['issue']['type']
					)
				]--;

				/*
				 * Keep track of what we remove
				 */
				if ( ! isset(
					$comments_removed[
						$pr_number
					]
				) ) {
					$comments_removed[
						$pr_number
					] = array();
				}

				$comments_removed[
					$pr_number
				][] = $pr_issue;

				$comments_to_remove--;
			}
		}

		/*
		 * Re-create array so to
		 * keep continuous ordering
		 * of index.
		 */
		$results[
			'issues'
		][
			$pr_number
		] = array_values(
			$results[
				'issues'
			][
				$pr_number
			]
		);
	}

	/*
	 * Populate '$prs_comments_maxed' which
	 * indicates which Pull-Requests have
	 * had number of comments posted limited.
	 */
	$prs_comments_maxed = array_map(
		'is_array',
		$comments_removed
	);


	vipgoci_log(
		'Removed issue comments from array of to be submitted ' .
			'comments to PRs due to limit constraints',
		array(
			'review_comments_total_max'	=> $options['review-comments-total-max'],
			'comments_removed'		=> $comments_removed,
		)
	);

	return;
}

/*
 * Filter away issues that we should ignore from the set
 * of results, according to --review-comments-ignore argument.
 * The issues to be ignored are specified as an array of
 * string-messages, all in lower-case.
 */

function vipgoci_results_filter_ignorable(
	$options,
	&$results
) {
	$comments_removed = array();

	vipgoci_log(
		'Removing comments to be ignored from results before submission',
		array(
			'messages-ignore' =>
				$options['review-comments-ignore'],
		)
	);


	foreach(
		$results['issues'] as
			$pr_number => $pr_issues_comments
	) {
		foreach(
			$pr_issues_comments as
				$pr_issue_key =>
				$pr_issue
		) {
			if ( in_array(
				strtolower(
					$pr_issue['issue']['message']
				),
				$options['review-comments-ignore'],
				true
			) ) {
				/*
				 * Found a message to ignore,
				 * remove it from the results-array.
				 */
				unset(
					$results[
						'issues'
					][
						$pr_number
					][
						$pr_issue_key
					]
				);

				/*
				 * Keep track of what we remove
				 */
				if ( ! isset(
					$comments_removed[
						$pr_number
					]
				) ) {
					$comments_removed[
						$pr_number
					] = array();
				}

				$comments_removed[
					$pr_number
				][] = $pr_issue;


				/*
				 * Keep statistics up-to-date
				 */
				$results[
					'stats'
				][
					$pr_issue['type']
				][
					$pr_number
				][
					strtolower(
						$pr_issue['issue']['type']
					)
				]--;
			}
		}

		/*
		 * Re-create the array in
		 * case of changes to keys,
		 */

		$results['issues'][ $pr_number ] = array_values(
			$results['issues'][ $pr_number ]
		);
	}

	vipgoci_log(
		'Removed ignorable comments',
		array(
			'comments-removed' => $comments_removed
		)
	);
}

