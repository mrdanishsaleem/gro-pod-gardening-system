jQuery(document).ready(function ($) {
  // Badge notification system
  function showBadgeNotification(badge) {
    if (typeof jQuery !== 'undefined') {
      const notification = $(`
      <div class="badge-notification" style="
          position: fixed;
          top: 20px;
          right: 20px;
          background: linear-gradient(135deg, #4CAF50, #45a049);
          color: white;
          padding: 15px 20px;
          border-radius: 10px;
          box-shadow: 0 4px 12px rgba(0,0,0,0.3);
          z-index: 9999;
          display: flex;
          align-items: center;
          gap: 10px;
          animation: slideInRight 0.5s ease-out;
      ">
          <div class="badge-icon" style="width: 40px; height: 40px;">
              <img src="${badge.image}" alt="${badge.title}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
          </div>
          <div>
              <div style="font-weight: bold; font-size: 16px;">🎉 Badge Earned!</div>
              <div style="font-size: 14px; opacity: 0.9;">${badge.title}</div>
          </div>
          <button onclick="$(this).parent().fadeOut()" style="
              background: none;
              border: none;
              color: white;
              font-size: 18px;
              cursor: pointer;
              padding: 0;
              margin-left: 10px;
          ">×</button>
      </div>
      `);

      $('body').append(notification);

      // Auto-hide after 5 seconds
      setTimeout(() => {
        notification.fadeOut();
      }, 5000);
    }
  }

  // Recipe view tracking for badge progress
  function trackRecipeView() {
    if (typeof jQuery !== 'undefined') {
      jQuery.ajax({
        url: tpgs_ajax.ajax_url,
        type: 'POST',
        data: {
          action: 'tpgs_track_recipe_view',
          nonce: tpgs_ajax.nonce
        },
        success: function(response) {
          if (response.success && response.data.badges_updated) {
            // Show badge notification
            if (response.data.new_badges && response.data.new_badges.length > 0) {
              showBadgeNotification(response.data.new_badges[0]);
            }
            // Refresh badges section if badge was earned
            refreshBadges();
          }
        },
        error: function() {
        }
      });
    }
  }

  // Test community events (for debugging - remove in production)
  window.testCommunityEvent = function(actionType) {
    jQuery.ajax({
      url: tpgs_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'tpgs_test_community_events',
        action_type: actionType,
        nonce: tpgs_ajax.nonce
      },
      success: function(response) {
        if (response.success) {
          if (response.data.badges_updated) {
            location.reload(); // Refresh to show updated badges
          }
        }
      },
      error: function() {
      }
    });
  };

  // Initialize Bootstrap tooltips for badges
  if (typeof bootstrap !== 'undefined') {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl, {
        delay: {
          show: 500,
          hide: 100
        },
        trigger: 'hover focus'
      });
    });
  }

  // Check if we're on a recipe page and track the view
  var currentUrl = window.location.href;
  var isRecipePage = currentUrl.includes('recipe') ||
    currentUrl.includes('/learn/') ||
    $('body').hasClass('single-recipe') ||
    $('body').hasClass('learn-recipe');

  if (isRecipePage) {
    trackRecipeView();
  }

  // Also track when user clicks on recipe links
  $('a[href*="recipe"], a[href*="/learn/"]').on('click', function() {
    setTimeout(trackRecipeView, 1000); // Track after navigation
  });

  // Function to manually refresh pod days remaining (for admin use or manual refresh)
  function refreshPodDaysRemaining() {
    $.ajax({
      url: tpgs_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'tpgs_refresh_pod_days',
        nonce: tpgs_ajax.nonce,
      },
      success: function (response) {
        if (response.success) {
          // Show a success message instead of reloading
          showMessage('Pod days updated successfully!', 'success');
        } else {
          // console.error('Failed to refresh pod days:', response.data);
          showMessage('Failed to update pod days', 'error');
        }
      },
      error: function (xhr, status, error) {
        showMessage('Error updating pod days', 'error');
      },
    });
  }

  // Helper function to show messages
  function showMessage(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alertHtml = `
      <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    `;

    // Insert message at the top of the dashboard
    $('.dashboard').prepend(alertHtml);

    // Auto-remove after 5 seconds
    setTimeout(function () {
      $('.alert').fadeOut();
    }, 5000);
  }

  // Function to calculate dynamic date range based on planting date and growth duration
  function calculateDateRange(plantingDate, growthDuration) {
    if (!plantingDate) {
      // Fallback: calculate from growth duration if no planting date
      const today = new Date();
      const plantedDate = new Date(
        today.getTime() - growthDuration * 24 * 60 * 60 * 1000,
      );

      return `${plantedDate.toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
      })} - ${today.toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
      })}`;
    }

    // Parse the planting date (assuming it's in YYYY-MM-DD format)
    const plantedDate = new Date(plantingDate);
    const harvestDate = new Date(
      plantedDate.getTime() + growthDuration * 24 * 60 * 60 * 1000,
    );

    return `${plantedDate.toLocaleDateString('en-US', {
      month: 'long',
      day: 'numeric',
      year: 'numeric',
    })} - ${harvestDate.toLocaleDateString('en-US', {
      month: 'long',
      day: 'numeric',
      year: 'numeric',
    })}`;
  }

  function refreshPodDisplay(podId, podData) {
    $.ajax({
      url: tpgs_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'tpgs_get_pod_html',
        nonce: tpgs_ajax.nonce,
        pod_id: podId,
        pod_data: podData,
      },
      success: function (response) {
        if (response.success) {
          $(`.pod-${podId}`).replaceWith(response.data.html);
        } else {
          // console.error('Failed to refresh pod:', response.data);
        }
      },
      error: function (xhr, status, error) {
        // console.error('RefreshPodDisplay Error:', status, error, xhr.responseText);
      },
    });
  }

  // ============ POD DETAILS FUNCTION ============
  function loadPodDetails(podId) {
    $.ajax({
      url: tpgs_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'tpgs_get_pod_details',
        nonce: tpgs_ajax.nonce,
        pod_id: podId,
      },
      beforeSend: function () {
        $('#podDetailModal .modal-content').html(`
                    <div class="modal-body text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `);
        $('#podDetailModal').modal('show');
      },
      success: function (response) {
        if (response.success) {
          $('#podDetailModal .modal-content').html(response.data.html);
        } else {
          $('#podDetailModal .modal-content').html(`
                        <div class="modal-body">
                            <div class="alert alert-danger">${response.data}</div>
                        </div>
                    `);
        }
      },
      error: function (xhr, status, error) {
        $('#podDetailModal .modal-content').html(`
                    <div class="modal-body">
                        <div class="alert alert-danger">Failed to load pod details: ${error}</div>
                    </div>
                `);
      },
    });
  }

  // ============ DASHBOARD V3 HANDLERS ============
  function initDashboardV3() {
    if (!$('.dashboard').length) return;

    $('.pod')
      .off('click')
      .on('click', function () {
        const podId = $(this).data('pod-id');
        const status = $(this).find('.pod-status').text().toLowerCase();
        if (status === 'empty') {
          // Show add plant modal (implement your add plant logic here)
          return;
        }
        $.ajax({
          url: tpgs_ajax.ajax_url,
          type: 'POST',
          data: {
            action: 'tpgs_get_pod_details',
            nonce: tpgs_ajax.nonce,
            pod_id: podId,
          },
          beforeSend: function () {
            $('#podDetailModal .modal-content').html(
              '<div class="modal-body text-center p-3"><i class="fas fa-spinner fa-spin"></i> Loading...</div>',
            );
            $('#podDetailModal').modal('show');
          },
          success: function (response) {
            if (response.success) {
              $('#podDetailModal .modal-content').html(response.data.html);
              $('#podDetailModal').modal('handleUpdate');
            } else {
              // console.error('Pod Details Error:', response.data);
              $('#podDetailModal .modal-content').html(
                '<div class="modal-body"><div class="alert alert-danger">Error: ' +
                  (response.data || 'Unknown error') +
                  '</div></div>',
              );
            }
          },
          error: function (xhr, status, error) {
            $('#podDetailModal .modal-content').html(
              '<div class="modal-body"><div class="alert alert-danger">An error occurred: ' +
                error +
                '</div></div>',
            );
            $('#podDetailModal').modal('hide');
          },
        });
      });

    // Note: Pod click handling is now consolidated in the main .pods-grid click handler above
  }

  function refreshPodDisplay(podId, podData) {
    $.ajax({
      url: tpgs_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'tpgs_get_pod_html',
        nonce: tpgs_ajax.nonce,
        pod_id: podId,
        pod_data: podData || {}, // Ensure pod_data is an object
        timestamp: new Date().getTime(), // Cache busting
      },
      success: function (response) {
        if (response.success) {
          const $newPod = $(response.data.html);
          if ($newPod.length) {
            $(`.pod-${podId}`).replaceWith($newPod);
            $newPod.hide().fadeIn(300); // Smooth transition
          } else {
          }
        } else {
          // console.error('Failed to refresh pod:', response.data);
        }
      },
      error: function (xhr, status, error) {
        // console.error('RefreshPodDisplay Error:', status, error, xhr.responseText);
      },
    });
  }

  function initStreakSystem() {
    const streakBtn = $('.streak-btn');
    if (!streakBtn.length) return;

    streakBtn.on('click', function () {
      $.ajax({
        url: tpgs_ajax.ajax_url,
        type: 'POST',
        data: {
          action: 'tpgs_get_streak_modal',
          nonce: tpgs_ajax.nonce,
        },
        beforeSend: function () {
          $('#streakModal').modal('show');
          $('#streakModal .modal-content').html(
            '<div class="modal-body text-center p-3"><i class="fas fa-spinner fa-spin"></i> Loading...</div>',
          );
        },
        success: function (response) {
          if (response.success) {
            $('#streakModal .modal-content').html(response.data.html);
            $('#streakModal').modal('handleUpdate');
            $('#streakModal').on(
              'change',
              '#streakPodSelect input[type="radio"]',
              function () {
                const podId = $(this).val();
                $.ajax({
                  url: tpgs_ajax.ajax_url,
                  type: 'POST',
                  data: {
                    action: 'tpgs_get_streak_modal',
                    nonce: tpgs_ajax.nonce,
                    pod_id: podId,
                  },
                  success: function (response) {
                    if (response.success) {
                      $('#streakModal .modal-content').html(response.data.html);
                      $('#streakModal').modal('handleUpdate');
                    }
                  },
                });
              },
            );
          } else {
            // console.error('AJAX Success Error:', response.data);
            $('#streakModal .modal-content').html(
              '<div class="modal-body"><div class="alert alert-danger">Error: ' +
                (response.data || 'Unknown error') +
                '</div></div>',
            );
          }
        },
        error: function (xhr, status, error) {
          // console.error('AJAX Error:', status, error, xhr.responseText);
          $('#streakModal .modal-content').html(
            '<div class="modal-body"><div class="alert alert-danger">An error occurred while loading the modal: ' +
              error +
              '</div></div>',
          );
          $('#streakModal').modal('hide');
        },
      });
    });

    $(document).on('click', '#saveStreak', function () {
      const pod = $('#streakPodSelect input[type="radio"]:checked').val();
      const actions = [];
      $('#streakActions input[type="checkbox"]:checked').each(function () {
        actions.push($(this).val());
      });

      if (pod && actions.length > 0) {
        $.ajax({
          url: tpgs_ajax.ajax_url,
          type: 'POST',
          data: {
            action: 'tpgs_log_streak',
            nonce: tpgs_ajax.nonce,
            pods: [pod],
            actions: actions,
          },
          beforeSend: function () {
            $('#saveStreak').prop('disabled', true).text('Saving...');
          },
          success: function (response) {
            if (response.success) {
              $('#streakModal').modal('hide');
              showSuccessModal(response.data.pod_id, response.data.actions);
              $('.streak-number').text(response.data.weekly_streak);
              $('.total-streak-days').text(
                'Total: ' + response.data.total_streak_days + ' Days',
              );

              // Refresh the entire streak section to show updated calendar
              // Add a small delay to ensure modal transitions complete
              setTimeout(function () {
                refreshStreakSection();
              }, 500);

              // Use returned HTML directly
              if (response.data.html) {
                $(`.pod-${response.data.pod_id}`).replaceWith(
                  response.data.html,
                );
                $(`.pod-${response.data.pod_id}`).hide().fadeIn(300);
              }
            } else {
              showAlert(response.data, 'error');
            }
          },
          complete: function () {
            $('#saveStreak').prop('disabled', false).text('Save');
          },
        });
      } else {
        showAlert('Please select one pod and at least one action', 'error');
      }
    });
  }

  function showSuccessModal(podId, actions) {
    const vegetableName =
      $('.pod-' + podId + ' .vegetable-name').text() || 'Plant'; // Fallback to pod data
    const modalHtml = `
        <div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Success!</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <p>You ${actions} ${vegetableName} in Pod ${podId}.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    $('body').append(modalHtml);
    $('#successModal').modal('show');
    setTimeout(() => $('#successModal').modal('hide'), 3000);
  }

  // ============ INITIALIZATION ============
  initDashboardV3();
  $('[data-bs-toggle="tooltip"]').tooltip();
  initStreakSystem();

  $('.pods-grid').on('click', '.pod', function () {
    const podId = $(this).data('pod-id');
    const podStatus = $(this).hasClass('empty-pod')
      ? 'empty'
      : $(this).hasClass('ready')
      ? 'ready'
      : 'growing';

    if (podStatus === 'empty') {
      $('#plantingModal').data('pod-id', podId);
      $('#plantingPodNumber').text(podId);
      $('#plantingModal').modal('show');
    } else {
      // Calculate dynamic date range
      const plantingDate = $(this).data('planting-date');
      const growthDuration = $(this).data('growth-duration') || 42;
      const daysRemaining = $(this).data('days') || 0;
      const dateRange = calculateDateRange(plantingDate, growthDuration);

      // Show harvest modal instead of pod details
      showHarvestModal(podId, {
        name: $(this).find('.vegetable-name').text() || 'Plant',
        icon: $(this).find('.vegetable-icon').attr('src') || '',
        growth_duration: growthDuration,
        days_remaining: daysRemaining,
        date_range: dateRange,
      });
    }
  });

  $(document).on('click', '.vegetable-item', function () {
    $('.vegetable-item').removeClass('selected');
    $(this).addClass('selected');
  });

  function refreshBadges() {
    $.ajax({
      url: tpgs_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'tpgs_refresh_badges',
        user_id: tpgs_ajax.user_id,
        nonce: tpgs_ajax.nonce,
      },
      success: function (response) {
        if (response.success) {
          // Find the badges section - try multiple selectors
          let badgesSection = $('.badges-section');
          if (badgesSection.length === 0) {
            badgesSection = $('.badges-card.badges-section');
          }
          if (badgesSection.length === 0) {
            badgesSection = $('.badges-card');
          }

          if (badgesSection.length > 0) {
            badgesSection.replaceWith(response.data.html);

            // Reinitialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip('dispose').tooltip();

            if (response.data.badges_lost > 0) {
              showAlert(
                `${response.data.badges_lost} badge(s) were removed`,
                'warning',
              );
            }

          } else {
            // console.error('Could not find badges section to update');
            showAlert(
              'Badges updated but could not refresh display. Please refresh the page.',
              'info',
            );
          }
        } else {
          // console.error('Badge refresh failed:', response.data);
          showAlert(
            'Failed to update badges: ' + (response.data || 'Unknown error'),
            'error',
          );
        }
      },
      error: function (xhr, status, error) {
        // console.error('Badge refresh failed:', xhr.responseText, status, error);
        showAlert('Failed to update badges. Please refresh the page.', 'error');
      },
    });
  }

  $('#confirmPlanting').on('click', function () {
    const podId = $('#plantingModal').data('pod-id');
    const vegetableId = $('.vegetable-item.selected').data('vegetable-id');

    if (!vegetableId) {
      showAlert('Please select a vegetable to plant', 'error');
      return;
    }

    $.ajax({
      url: tpgs_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'tpgs_plant_vegetable',
        nonce: tpgs_ajax.nonce,
        pod_id: podId,
        vegetable_id: vegetableId,
      },
      beforeSend: function () {
        $('#confirmPlanting').prop('disabled', true).text('Planting...');
      },
      success: function (response) {
        if (response.success) {
          // Update pod display
          $(`.pod-${podId}`).replaceWith(response.data.pod_html);

          // Close planting modal if open
          $('#plantingModal').modal('hide');

          // Show success modal
          if (response.data.modal_html) {
            $('#plantSuccessModal').remove();
            $('body').append(response.data.modal_html);
            var successModal = new bootstrap.Modal(
              document.getElementById('plantSuccessModal'),
            );
            successModal.show();

            // After success modal is hidden, refresh pod details
            $('#plantSuccessModal').on('hidden.bs.modal', function () {
              loadPodDetails(podId); // This will show updated pod details
            });
          }

          // Update other UI elements
          updateActivePodsCount(response.data.active_count);
          if (response.data.badges_updated) {
            refreshBadges();

            // Fallback: if badge refresh fails, show a message to refresh the page
            setTimeout(() => {
              if (
                $('.badges-section').length === 0 &&
                $('.badges-card.badges-section').length === 0
              ) {
                showAlert(
                  'Badges were updated but the display could not be refreshed. Please refresh the page to see the changes.',
                  'info',
                );
              }
            }, 2000);
          }
          if (response.data.next_harvest) {
            updateNextHarvest(response.data.next_harvest);
          }
        } else {
          showAlert(response.data, 'error');
        }
      },
      error: function (xhr, status, error) {
        showAlert('An error occurred: ' + error, 'error');
      },
      complete: function () {
        $('#confirmPlanting').prop('disabled', false).text('Plant');
      },
    });
  });

  function loadPodDetails(podId) {
    $.ajax({
      url: tpgs_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'tpgs_get_pod_details',
        nonce: tpgs_ajax.nonce,
        pod_id: podId,
      },
      beforeSend: function () {
        $('#podDetailModal .modal-content').html(
          '<div class="modal-body text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>',
        );
        $('#podDetailModal').modal('show');
      },
      success: function (response) {
        if (response.success) {
          $('#podDetailModal .modal-content').html(response.data.html);
        } else {
          $('#podDetailModal .modal-content').html(
            `<div class="modal-body"><div class="alert alert-danger">${response.data}</div></div>`,
          );
        }
      },
      error: function (xhr, status, error) {
        $('#podDetailModal .modal-content').html(
          `<div class="modal-body"><div class="alert alert-danger">Failed to load pod details: ${error}</div></div>`,
        );
      },
    });
  }

  $(document).on('click', '#updatePodDate', function () {
    const podId = $(this).data('pod-id');
    const newDate = $('#podDatePicker').val();

    if (!podId || podId < 1 || podId > 12) {
      showAlert('Invalid pod selection', 'error');
      return;
    }

    if (!newDate) {
      showAlert('Please select a valid date', 'error');
      return;
    }

    $.ajax({
      url: tpgs_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'tpgs_update_pod_date',
        nonce: tpgs_ajax.nonce,
        pod_id: podId,
        new_date: newDate,
      },
      beforeSend: function () {
        $('#updatePodDate').prop('disabled', true).text('Updating...');
      },
      success: function (response) {
        if (response.success) {
          $(`.pod-${podId}`).replaceWith(response.data.pod_html);
          $('.pod-counter span').text(response.data.active_count);
          if ($('#podDetailModal').is(':visible')) {
            $('.days-remaining-value').text(response.data.days_remaining);
            $('.pod-status-value').text(response.data.status_text);
            if (response.data.status === 'ready') {
              $('.pod-status-value')
                .removeClass('text-warning')
                .addClass('text-success');
            }
          }
          if (response.data.next_harvest) {
            updateNextHarvest(response.data.next_harvest);
          } else {
            updateNextHarvest(null);
          }
          showAlert('Pod updated successfully!', 'success');
        } else {
          showAlert(response.data, 'error');
        }
      },
      error: function (xhr) {
        let errorMsg = 'Failed to update pod';
        if (xhr.responseJSON && xhr.responseJSON.data) {
          errorMsg = xhr.responseJSON.data;
        }
        showAlert(errorMsg, 'error');
      },
      complete: function () {
        $('#updatePodDate').prop('disabled', false).text('Update Date');
      },
    });
  });

  // Reset Pod functionality
  $(document).on('click', '#resetPod', function () {
    if (
      !confirm(
        'Are you sure you want to reset this pod? This cannot be undone.',
      )
    ) {
      return;
    }

    const podId = $(this).data('pod-id');

    $.ajax({
      url: tpgs_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'tpgs_reset_pod',
        nonce: tpgs_ajax.nonce,
        pod_id: podId,
      },
      beforeSend: function () {
        $('#resetPod').prop('disabled', true).text('Resetting...');
      },
      success: function (response) {
        if (response.success) {
          $(`.pod-${podId}`).replaceWith(response.data.pod_html);
          $('.pod-counter span').text(response.data.active_count);
          $('#podDetailModal').modal('hide');
          if (response.data.badges_updated) {
            refreshBadges();

            // Fallback: if badge refresh fails, show a message to refresh the page
            setTimeout(() => {
              if (
                $('.badges-section').length === 0 &&
                $('.badges-card.badges-section').length === 0
              ) {
                showAlert(
                  'Badges were updated but the display could not be refreshed. Please refresh the page to see the changes.',
                  'info',
                );
              }
            }, 2000);
          }
          if (response.data.next_harvest) {
            updateNextHarvest(response.data.next_harvest);
          } else {
            updateNextHarvest(null);
          }
          showAlert(response.data.message, 'success');
        } else {
          showAlert(response.data, 'error');
        }
      },
      error: function (xhr, status, error) {
        showAlert('An error occurred: ' + error, 'error');
      },
      complete: function () {
        $('#resetPod').prop('disabled', false).text('Reset Pod');
      },
    });
  });

  function updateStreakCalendar() {
    // Get today's date and week start
    const today = new Date();
    const weekStart = new Date(today);
    const dayOfWeek = today.getDay();
    const daysToMonday = dayOfWeek === 0 ? 6 : dayOfWeek - 1; // Sunday = 0, Monday = 1
    weekStart.setDate(today.getDate() - daysToMonday);

    // Find today's day element in the calendar
    const dayElements = $('.week-days .day');
    const todayIndex = Math.floor((today - weekStart) / (1000 * 60 * 60 * 24));

    if (todayIndex >= 0 && todayIndex < dayElements.length) {
      // Add completed class to today's day
      $(dayElements[todayIndex]).addClass('completed');
    } else {
    }
  }

  function refreshStreakSection() {
    // Alternative approach: refresh the entire streak section via AJAX
    $.ajax({
      url: tpgs_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'tpgs_get_streak_section',
        nonce: tpgs_ajax.nonce,
      },
      success: function (response) {
        if (response.success) {
          $('.streak-card').replaceWith(response.data.html);

          // Reinitialize the streak button click handler for the new HTML
          initStreakSystem();
        } else {
          // console.error('Failed to refresh streak section:', response.data);
          // Fallback to simple calendar update
          updateStreakCalendar();
        }
      },
      error: function (xhr, status, error) {
        // console.error('Error refreshing streak section:', error);
        // Fallback to simple calendar update
        updateStreakCalendar();
      },
    });
  }

  function showAlert(message, type = 'success') {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alertHtml = `<div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;

    $('.tpgs-alert-container').remove();
    $('body').append(`<div class="tpgs-alert-container"></div>`);
    $('.tpgs-alert-container').html(alertHtml);

    setTimeout(() => {
      $('.tpgs-alert-container .alert').alert('close');
    }, 5000);
  }

  function checkNewBadges(userId) {
    if (!userId) return;

    $.ajax({
      url: tpgs_ajax.ajax_url,
      type: 'GET',
      data: {
        action: 'tpgs_check_badges',
        user_id: userId,
        nonce: tpgs_ajax.nonce,
      },
      success: function (response) {
        if (response.success && response.badges?.length) {
          response.badges.forEach((badge) => {
            showBadgeNotification(badge);
          });
        }
      },
    });
  }


  // Harvest Modal Functions
  function showHarvestModal(podId, plantData) {
    // Update modal title
    $('#harvestModalTitle').text(`Pod ${podId} Ready to harvest!`);

    // Update plant image and make it visible
    if (plantData.icon) {
      $('#harvestPlantImage')
        .attr('src', plantData.icon)
        .attr('alt', plantData.name || 'Plant')
        .show();
      $('.harvest-plant-image').show();
    } else {
      $('.harvest-plant-image').hide();
    }

    // Update description with actual plant name and icon
    const plantIconHtml = plantData.icon
      ? `<img src="${plantData.icon}" alt="${
          plantData.name || 'plant'
        }" class="harvest-desc-icon" style="width: 20px; height: 20px; vertical-align: middle; margin-left: 4px;">`
      : '';
    $('#harvestDescription').html(
      `Look at that growth—your ${
        plantData.name || 'plant'
      } is thriving! ${plantIconHtml}`,
    );

    // Update growing time card with actual data
    if (plantData.growth_duration) {
      $('#growingDays').text(`${plantData.growth_duration} days`);
    } else {
      $('#growingDays').text('42 days'); // fallback
    }

    // Update days remaining display
    if (plantData.days_remaining !== undefined) {
      if (plantData.days_remaining === 0) {
        $('#growingDays').text('Ready to harvest!');
        $('#growingLabel').text('Status');
      } else {
        $('#growingDays').text(`${plantData.days_remaining} days`);
        $('#growingLabel').text('Days remaining to harvest');
      }
    }

    if (plantData.date_range) {
      $('#growingDates').text(plantData.date_range);
    } else {
      // Generate date range if not provided
      const today = new Date();
      const plantedDate = new Date(
        today.getTime() -
          (plantData.growth_duration || 42) * 24 * 60 * 60 * 1000,
      );
      const dateRange = `${plantedDate.toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
      })} - ${today.toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
      })}`;
      $('#growingDates').text(dateRange);
    }

    // Update harvest message
    $('#harvestMessage').text(
      `You've reached the finish line of your first hydroponic cycle. Harvest, enjoy, and get ready to plant your next crop. 🎉 You did it! Time to taste the results.`,
    );

    // Store pod data for harvest action
    $('#harvestModal').data('pod-id', podId);
    $('#harvestModal').data('plant-data', plantData);

    // Show the modal
    $('#harvestModal').modal('show');

    // Refresh days remaining data when modal is shown
    refreshModalDaysRemaining(podId);
  }

  // Function to refresh days remaining data for modals
  function refreshModalDaysRemaining(podId) {
    // Get the current pod element
    const $pod = $(`.pod[data-pod-id="${podId}"]`);
    if ($pod.length) {
      // Update the days remaining from the pod's current data
      const currentDays = $pod.data('days');
      if (currentDays !== undefined) {
        // Update the modal display with current data
        if (currentDays === 0) {
          $('#growingDays').text('Ready to harvest!');
          $('#growingLabel').text('Status');
        } else {
          $('#growingDays').text(`${currentDays} days`);
          $('#growingLabel').text('Days remaining to harvest');
        }

        // Also update the plant data stored in the modal
        const plantData = $('#harvestModal').data('plant-data');
        if (plantData) {
          plantData.days_remaining = currentDays;
          $('#harvestModal').data('plant-data', plantData);
        }
      }
    }
  }

  // Handle clicks on ready-to-harvest pods
  $(document).on('click', '.pod.ready-to-harvest', function (e) {
    e.preventDefault();
    e.stopPropagation();

    const $pod = $(this);
    const podId = $pod.data('pod-id');

    // Get plant data from data attributes
    const plantName =
      $pod.data('plant-name') || $pod.find('.plant-name').text() || 'Plant';
    const plantIcon = $pod.find('.vegetable-icon').attr('src') || '';
    const growthDuration = $pod.data('growth-duration') || 42;
    const plantingDate = $pod.data('planting-date');
    const daysRemaining = $pod.data('days') || 0;

    // Generate date range using the calculateDateRange function
    const dateRange = calculateDateRange(plantingDate, growthDuration);

    // Show harvest modal for this pod with accurate data
    showHarvestModal(podId, {
      name: plantName,
      icon: plantIcon,
      growth_duration: growthDuration,
      date_range: dateRange,
      days_remaining: daysRemaining,
    });
  });

  // Function to update next harvest section
  function updateNextHarvest(nextHarvestData) {
    const $nextHarvestSection = $('.next-harvest');
    if (!$nextHarvestSection.length) return;

    const $harvestInfo = $nextHarvestSection.find('.harvest-info');

    if (
      nextHarvestData &&
      nextHarvestData.name &&
      nextHarvestData.days !== undefined
    ) {
      // Check if we need to restore the h3 structure
      if ($harvestInfo.find('.pods-count').length) {
        $harvestInfo.find('.pods-count').replaceWith('<h3></h3>');
      }

      // Update the harvest info
      $nextHarvestSection
        .find('.harvest-info h3')
        .text(`${nextHarvestData.name} in ${nextHarvestData.days} days`);

      // Update the icon if available
      if (nextHarvestData.icon) {
        $nextHarvestSection
          .find('.harvest-icon img')
          .attr('src', nextHarvestData.icon);
        $nextHarvestSection.find('.harvest-icon').show();
      } else {
        $nextHarvestSection.find('.harvest-icon').hide();
      }
    } else {
      // No next harvest - show "0 days" state with same styling as active pods
      // Check if we need to replace h3 with pods-count structure
      if ($harvestInfo.find('h3').length) {
        $harvestInfo
          .find('h3')
          .replaceWith(
            '<div class="pods-count pod-counter"><span class="active-count">0</span> days</div>',
          );
      } else if ($harvestInfo.find('.pods-count').length) {
        // Update existing pods-count structure
        $harvestInfo.find('.active-count').text('0');
      }
      $nextHarvestSection.find('.harvest-icon').hide();
    }
  }

  // Function to update active pods count
  function updateActivePodsCount(activeCount) {
    const $activeCountElement = $('.active-count');
    if ($activeCountElement.length) {
      $activeCountElement.text(activeCount);
    }
  }

  // Handle harvest now button click - show confirmation modal
  $(document).on('click', '#harvestNowBtn', function () {
    const podId = $('#harvestModal').data('pod-id');
    const plantData = $('#harvestModal').data('plant-data');

    if (podId && plantData) {
      // Show confirmation modal as overlay
      showHarvestConfirmationModal(podId, plantData);
    }
  });

  // Show confirmation modal for harvest
  function showHarvestConfirmationModal(podId, plantData) {
    // Get days remaining from the modal or plant data
    const daysRemaining = plantData.days_remaining || 0;

    // Update confirmation modal content
    $('#confirmationModalTitle').text('Confirm Harvest');

    // Update plant image
    if (plantData.icon) {
      $('#confirmationPlantImage')
        .attr('src', plantData.icon)
        .attr('alt', plantData.name || 'Plant')
        .show();
    } else {
      $('#confirmationPlantImage').hide();
    }

    // Update confirmation message with remaining days
    $('#remainingDays').text(daysRemaining);

    // Show appropriate message based on days remaining
    if (daysRemaining === 0) {
      $('#confirmationMessage').html(
        `Are you sure you want to harvest now? Your plant is ready! 🎉`,
      );
    } else {
      $('#confirmationMessage').html(
        `Are you sure you want to harvest now? There are still <span id="remainingDays">${daysRemaining}</span> day${
          daysRemaining !== 1 ? 's' : ''
        } remaining.`,
      );
    }

    // Store pod data for harvest action
    $('#harvestConfirmationModal').data('pod-id', podId);
    $('#harvestConfirmationModal').data('plant-data', plantData);

    // Hide harvest modal and show confirmation modal
    $('#harvestModal').modal('hide');
    setTimeout(() => {
      $('#harvestConfirmationModal').modal('show');
    }, 300);
  }

  // Handle confirmation modal "No" button click
  $(document).on(
    'click',
    '#harvestConfirmationModal .cancel-harvest-btn',
    function () {
      // Close confirmation modal and go back to harvest modal
      $('#harvestConfirmationModal').modal('hide');
      setTimeout(() => {
        $('#harvestModal').modal('show');
      }, 300);
    },
  );

  // Handle confirmation modal "Yes" button click
  $(document).on('click', '#confirmHarvestBtn', function () {
    const podId = $('#harvestConfirmationModal').data('pod-id');
    const plantData = $('#harvestConfirmationModal').data('plant-data');

    if (podId && plantData) {

      // Disable button to prevent double-clicking
      $('#confirmHarvestBtn').prop('disabled', true).text('Harvesting...');

      $.ajax({
        url: tpgs_ajax.ajax_url,
        type: 'POST',
        data: {
          action: 'tpgs_harvest_pod',
          nonce: tpgs_ajax.nonce,
          pod_id: podId,
        },
        success: function (response) {
          if (response.success) {
            // Close both modals
            $('#harvestConfirmationModal').modal('hide');
            $('#harvestModal').modal('hide');

            // Update the pod HTML
            $(`.pod-${podId}`).replaceWith(response.data.pod_html);

            // Update next harvest section
            if (response.data.next_harvest) {
              updateNextHarvest(response.data.next_harvest);
            }

            // Update active pods count
            if (response.data.active_count !== undefined) {
              updateActivePodsCount(response.data.active_count);
            }

            // Show success message
            showAlert('Harvest completed successfully!', 'success');

            // Refresh badges if needed
            if (response.data.badges_updated) {
              refreshBadges();
            }
          } else {
            showAlert(response.data || 'Failed to harvest pod', 'error');
          }
        },
        error: function (xhr, status, error) {
          // console.error('Harvest Error:', status, error, xhr.responseText);
          showAlert('Failed to harvest pod. Please try again.', 'error');
        },
        complete: function () {
          // Re-enable button
          $('#confirmHarvestBtn').prop('disabled', false).text('Yes');
        },
      });
    }
  });

  $('body').append(
    '<div id="badge-notifications-container" class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999"></div>',
  );
  $('[data-bs-toggle="tooltip"]').tooltip();

  // Manual badge refresh button
  $(document).on('click', '.refresh-badges-btn', function () {
    refreshBadges();
  });

  // Note: Empty pod click handling is now consolidated in the main .pods-grid click handler

  // Intro Modal Functionality
  function initIntroModal() {
    let currentStep = 1;
    const totalSteps = 5;

    // Check if user has seen the intro before
    const introKey = tpgs_ajax.user_id
      ? `tpgs_intro_completed_${tpgs_ajax.user_id}`
      : 'tpgs_intro_completed';
    const hasSeenIntro = localStorage.getItem(introKey);
    const isNewUser = tpgs_ajax.is_new_user || false; // Get from AJAX data

    // Only show intro for new users who haven't seen it
    if (hasSeenIntro || !isNewUser) {
      return; // Don't show intro if already completed or not a new user
    }

    // Show intro modal on page load
    setTimeout(() => {
      $('#introModal').modal('show');
    }, 1000);

    // Next button functionality
    $(document).on('click', '.intro-next-btn', function () {
      if (currentStep < totalSteps) {
        goToStep(currentStep + 1);
      } else {
        // On last step, complete the intro
        completeIntro();
      }
    });

    // Skip button functionality
    $(document).on('click', '.intro-skip-btn', function () {
      completeIntro();
    });

    // Dot navigation
    $(document).on('click', '.intro-dot', function () {
      const step = parseInt($(this).data('step'));
      goToStep(step);
    });

    // Keyboard navigation
    $(document).on('keydown', function (e) {
      if (!$('#introModal').is(':visible')) return;

      if (e.key === 'ArrowRight' || e.key === ' ') {
        e.preventDefault();
        if (currentStep < totalSteps) {
          goToStep(currentStep + 1);
        } else {
          completeIntro();
        }
      } else if (e.key === 'ArrowLeft') {
        e.preventDefault();
        if (currentStep > 1) {
          goToStep(currentStep - 1);
        }
      } else if (e.key === 'Escape') {
        completeIntro();
      }
    });

    function goToStep(step) {
      // Hide current step
      $(`.intro-step[data-step="${currentStep}"]`).hide();

      // Show new step
      $(`.intro-step[data-step="${step}"]`).show();

      // Update dots
      $('.intro-dot').removeClass('active');
      $(`.intro-dot[data-step="${step}"]`).addClass('active');

      // Update progress text
      $('.intro-progress').text(`${step}/${totalSteps}`);

      // Update button text
      if (step === totalSteps) {
        $('.intro-next-btn').text('Get Started');
      } else {
        $('.intro-next-btn').text('Next');
      }

      currentStep = step;
    }

    function completeIntro() {
      // Mark intro as completed
      const introKey = tpgs_ajax.user_id
        ? `tpgs_intro_completed_${tpgs_ajax.user_id}`
        : 'tpgs_intro_completed';
      localStorage.setItem(introKey, 'true');

      // Also mark as completed on server
      $.ajax({
        url: tpgs_ajax.ajax_url,
        type: 'POST',
        data: {
          action: 'tpgs_mark_intro_completed',
          nonce: tpgs_ajax.nonce,
          user_id: tpgs_ajax.user_id,
        },
        success: function (response) {
        },
        error: function (xhr, status, error) {
          // console.error('Failed to mark intro as completed:', error);
        },
      });

      // Hide modal
      $('#introModal').modal('hide');

      // Optional: Show a welcome message
      showAlert('Welcome to your 12-Pod Garden! 🌱', 'success');
    }
  }

  // Initialize intro modal
  initIntroModal();

  // Function to reset intro modal (for testing)
  window.resetIntroModal = function () {
    const introKey = tpgs_ajax.user_id
      ? `tpgs_intro_completed_${tpgs_ajax.user_id}`
      : 'tpgs_intro_completed';
    localStorage.removeItem(introKey);
    // Also remove from server
    $.ajax({
      url: tpgs_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'tpgs_mark_intro_completed',
        nonce: tpgs_ajax.nonce,
        user_id: tpgs_ajax.user_id,
        reset: true,
      },
      success: function (response) {
        location.reload();
      },
      error: function (xhr, status, error) {
        // console.error('Failed to reset intro:', error);
        location.reload();
      },
    });
  };

  // Function to show intro modal manually (for testing)
  window.showIntroModal = function () {
    $('#introModal').modal('show');
  };

  // Function to simulate new user (for testing)
  window.simulateNewUser = function () {
    const introKey = tpgs_ajax.user_id
      ? `tpgs_intro_completed_${tpgs_ajax.user_id}`
      : 'tpgs_intro_completed';
    localStorage.removeItem(introKey);
    // Force the intro to show by temporarily setting is_new_user
    tpgs_ajax.is_new_user = true;
    // $("#introModal").modal("show");
    initIntroModal();
  };
});
