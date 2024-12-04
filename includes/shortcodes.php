<?php
/**
 * PropTrack Shortcodes.
 */
// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Agent Page Shortcode.
 */
function PropTrackAgentShortcode($atts)
{
    return 'Agent Shortcode';
}
add_shortcode('proptrack_agent', 'PropTrackAgentShortcode');

/**
 * Appraisal Shortcode.
 */
function PropTrackAppraisalShortcode($atts)
{
    return 'Appraisal Shortcode';
}
add_shortcode('proptrack_appraisal', 'PropTrackAppraisalShortcode');