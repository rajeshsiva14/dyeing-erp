/**
 * MyPageUI - Reusable JavaScript for Premium "My" Pages
 * Standardizes behavior for drawers, filters, and selection bars.
 */

if (typeof MyPageUI === "undefined") {
  var MyPageUI = {
    // -----------------------------
    // Common Sorting Engine
    // -----------------------------
    sortStates: {},

    getSortState: function (key, defaultCol = "tid") {
      if (!this.sortStates[key]) {
        this.sortStates[key] = {
          column: defaultCol,
          order: "DESC",
        };
      }
      return this.sortStates[key];
    },

    toggleSort: function (key) {
      const s = this.getSortState(key);
      s.order = s.order === "DESC" ? "ASC" : "DESC";
      return s;
    },

    setSort: function (key, col) {
      const s = this.getSortState(key);
      if (s.column === col) {
        s.order = s.order === "ASC" ? "DESC" : "ASC";
      } else {
        s.column = col;
        s.order = "ASC";
      }
      return s;
    },

    resetSort: function (key, defaultCol = "tid", defaultOrder = "DESC") {
      this.sortStates[key] = {
        column: defaultCol,
        order: defaultOrder,
      };
      return this.sortStates[key];
    },

    // Pastel colors for avatars
    pastelColors: [
      "#E3F2FD",
      "#F3E5F5",
      "#E8F5E9",
      "#FFF3E0",
      "#F1F8E9",
      "#FCE4EC",
      "#E0F2F1",
    ],

    /**
     * Get a consistent pastel color based on a string (e.g., name)
     */
    getColorFromName: function (name) {
      if (!name) return this.pastelColors[0];
      let charCodeSum = 0;
      for (let i = 0; i < name.length; i++) charCodeSum += name.charCodeAt(i);
      return this.pastelColors[charCodeSum % this.pastelColors.length];
    },

    /**
     * Initializer for common page behaviors
     * @param {Object} config - Configuration object
     */
    init: function (config = {}) {
      const {
        drawerId = "sideDrawer",
        drawerOverlayId = "drawerOverlay",
        filterPaneId = "myfilterRightPane",
        filterOverlayId = "myfilterOverlay",
        checkboxClass = "my_tbl_checkbox",
        selectAllId = "allcb",
      } = config;

      // Drawer Controls
      // Unbind previous listeners to prevent duplicates if init is called multiple times
      $(document).off(
        "click",
        `#closeDrawer, #${drawerOverlayId}, .close-drawer-btn`,
      );
      $(document).off("click", "#filter_search");
      $(document).off("click", `#myfilterClosePane, #${filterOverlayId}`);
      $(document).off("change", `.${checkboxClass}, #${selectAllId}`);
      $(document).off("keyup", "#global_search_table");

      // Drawer Controls
      $(document).on(
        "click",
        `#closeDrawer, #${drawerOverlayId}, .close-drawer-btn`,
        () => {
          this.closeDrawer(drawerId, drawerOverlayId);
        },
      );

      // Filter Pane Controls
      $(document).on("click", "#filter_search", () => {
        $(`#${filterPaneId}`).addClass("active");
        $(`#${filterOverlayId}`).addClass("active");
      });

      $(document).on("click", `#myfilterClosePane, #${filterOverlayId}`, () => {
        $(`#${filterPaneId}`).removeClass("active");
        $(`#${filterOverlayId}`).removeClass("active");
      });

      // Selection Logic
      $(document).on("change", `.${checkboxClass}, #${selectAllId}`, (e) => {
        if (e.target.id === selectAllId) {
          $(`.${checkboxClass}`).prop("checked", $(e.target).is(":checked"));
        } else {
          const allChecked =
            $(`.${checkboxClass}`).length ===
            $(`.${checkboxClass}:checked`).length;
          $(`#${selectAllId}`).prop("checked", allChecked);
        }

        if (typeof window.updateActionBar === "function") {
          window.updateActionBar();
        }
      });

      // Global Search (Client-side)
      $(document).on("keyup", "#global_search_table", function () {
        let val = $(this).val().toLowerCase();
        $(".tr_premium_row").filter(function () {
          $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1);
        });
      });

      // Initialize sorting if callback provided
      if (config.onSort) {
        window.toggleTableSort = (col = "tid") => {
          const key = config.sortKey || "default";
          const state = this.getSortState(key, col);

          // If switching column → start ASC
          if (state.column !== col) {
            state.column = col;
            state.order = "ASC";
          } else {
            // Same column → toggle
            state.order = state.order === "ASC" ? "DESC" : "ASC";
          }

          config.onSort(state);
        };

        window.setTableSort = (col) => {
          const sort = this.setSort(config.sortKey || "default", col);
          config.onSort(sort);
        };
      }
    },

    /**
     * Close the filter pane
     */
    closeFilterPane: function (
      filterPaneId = "myfilterRightPane",
      filterOverlayId = "myfilterOverlay",
    ) {
      $(`#${filterPaneId}`).removeClass("active");
      $(`#${filterOverlayId}`).removeClass("active");
    },

    /**
     * Update the selection action bar
     * @param {number} count - Number of selected items
     * @param {string} barId - Selection bar container ID
     */
    updateActionBar: function (count, barId = "selection_info") {
      const $bar = $(`#${barId}`);
      if (count > 0) {
        $bar.addClass("active");
        if ($("#selected_count_badge").length) {
          $("#selected_count_badge").text(count);
        }
      } else {
        $bar.removeClass("active");
      }
    },

    /**
     * Generic bulk action confirmation
     */
    confirmBulkAction: function (options) {
      const {
        selectedCount,
        actionName = "Delete",
        confirmButtonColor = "#d33",
        onConfirm,
      } = options;

      if (selectedCount === 0) {
        Swal.fire({
          icon: "warning",
          title: "No Selection",
          text: "Please select items first",
        });
        return;
      }

      Swal.fire({
        title: "Are you sure?",
        text: `You are about to ${actionName.toLowerCase()} ${selectedCount} items.`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: confirmButtonColor,
        confirmButtonText: `Yes, ${actionName}`,
      }).then((result) => {
        if (result.isConfirmed) onConfirm();
      });
    },
    closeDrawer: function (drawerId, overlayId) {
      $(`#${drawerId}`).removeClass("active open");
      $(`#${overlayId}`).removeClass("active show");
    },

    /**
     * Open a drawer and its overlay
     */
    openDrawer: function (drawerId, overlayId) {
      $(`#${drawerId}`).addClass("active open");
      $(`#${overlayId}`).addClass("active show");
    },

    /**
     * Standardized row click handler
     * @param {Event} e - Click event
     * @param {string} baseUrl - Base URL for redirection
     */
    handleRowClick: function (e, baseUrl) {
      const $target = $(e.target);
      // Exclude clicks on interactive elements
      if ($target.closest("td").is(":first-child, :last-child")) return;
      if (
        $target.closest(".custom-checkbox, button, a, .info-trigger, .no-click")
          .length
      )
        return;

      const $row = $target.closest("tr");
      const id = $row.data("id");
      if (id) {
        // If baseUrl contains query parameters (e.g., ?key1=update&updateKey=), append ID directly
        // Otherwise, use hash-based routing (e.g., b2b-add.php#123)
        const separator = baseUrl.includes("?") ? "" : "#";
        window.location.href = `${baseUrl}${separator}${id}`;
      }
    },

    /**
     * Show/Hide loader overlay
     */
    toggleLoader: function (show = true) {
      const $loader = $("#global-loader-overlay");
      if (show) $loader.css("display", "flex").fadeIn(200);
      else $loader.fadeOut(300);
    },

    /**
     * Standardized bulk delete handler
     * @param {Object} options - { checkedIDs, url, onSuccess, confirmText }
     */
    deleteItems: function (options) {
      const {
        checkedIDs,
        url,
        onSuccess,
        confirmText = "Are you sure you want to delete the selected items?",
        isInvoice = false, // New parameter to identify invoice deletion
        checkEInvoice = false, // Check for e-invoice status
        getRowData = null, // Function to get row-specific data
      } = options;

      if (!checkedIDs || checkedIDs.length === 0) {
        Swal.fire({
          icon: "warning",
          title: "No Selection",
          text: "Please select items first",
        });
        return;
      }

      // Invoice-specific E-invoice validation
      if (isInvoice && checkEInvoice && getRowData) {
        let e_inv_sel = [];
        let all_data = 0;

        checkedIDs.forEach((id) => {
          const rowData = getRowData(id);
          if (rowData && rowData.e_invoice_status === "E-invoiced") {
            e_inv_sel.push(rowData.generate_no || id);
          }
          all_data++;
        });

        // If only one item selected and it's E-invoiced, prevent deletion
        if (e_inv_sel.length === 1 && all_data === 1) {
          Swal.fire({
            icon: "warning",
            title: "Cannot Delete",
            text: "You can't delete the invoice once it is E-Invoiced!",
            timer: 4000,
            showConfirmButton: false,
          });
          return;
        }

        // If multiple items selected and some are E-invoiced, show warning
        if (all_data > 1 && e_inv_sel.length >= 1) {
          confirmText =
            "Are you sure you want to delete the selected items?<br><br><strong>However, you cannot delete invoices that are E-invoiced.</strong>";
        }
      }

      // Show confirmation dialog
      Swal.fire({
        title: "Are you sure?",
        html: confirmText, // Changed to 'html' to support HTML content
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#dc3545",
        confirmButtonText: "Yes, Delete",
        cancelButtonText: "Cancel",
      }).then((result) => {
        if (result.isConfirmed) {
          this.toggleLoader(true);

          $.ajax({
            url: url,
            type: "POST",
            data: { delete_key: checkedIDs.join(",") },
            dataType: "json",
            success: (response) => {
              this.toggleLoader(false);
              if (response.success) {
                Swal.fire({
                  icon: "success",
                  title: "Deleted!",
                  text: response.message,
                  timer: 2000,
                  showConfirmButton: false,
                }).then(() => {
                  if (onSuccess) onSuccess();
                });
              } else {
                Swal.fire("Error", response.message, "error");
              }
            },
            error: () => {
              this.toggleLoader(false);
              Swal.fire(
                "Error",
                "Server error occurred during deletion",
                "error",
              );
            },
          });
        }
      });
    },
  };
}
