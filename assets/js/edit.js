$(function () {
  /* 2026-10-19 修复：原先无条件执行 new Sortable(...)，而 Sortable.min.js 已在
     10-16 的性能优化中从 head 移除，导致每个页面都抛 ReferenceError: Sortable is not defined。
     现在改为：只有存在 #sortgrid（发布/编辑页）时才加载 Sortable 并初始化，其它页面直接跳过。 */
  var sortGrid = document.getElementById("sortgrid");
  if (sortGrid) {
    var initSortable = function () {
      if (typeof Sortable === "undefined") return;
      new Sortable(sortGrid, {
        animation: 150,
        handle: ".td",
        filter: ".new-plus",
        ghostClass: "blue-background-class",
      });
    };
    if (typeof Sortable !== "undefined") {
      initSortable();
    } else {
      var selfScript = document.querySelector('script[src*="assets/js/edit.js"]');
      var themeBase = selfScript ? selfScript.src.replace(/assets\/js\/edit\.js.*$/, "") : "";
      var s = document.createElement("script");
      s.src = themeBase + "assets/js/Sortable.min.js";
      s.onload = initSortable;
      document.head.appendChild(s);
    }
  }

  let thisFormAction = $($("form")[0]).attr("action");
  let currentImgLength = $("img.td").length || 0;

  $(".new-plus").click(function () {
    $("#imageUpload").click();
  });

  let xsvg = $("#xsvg").val();
  let isUploading = false;
  let originalPlusHtml = $(".new-plus").html();

  /**
   * 客户端 Canvas 图片压缩（高性能兼容版，专为手机大图与移动端WebView优化）
   * @param {File} file 原始图片文件
   * @param {number} maxDimension 最大长边像素（默认 1920）
   * @param {number} quality 压缩质量（0~1，默认 0.82）
   * @returns {Promise<File|Blob>} 压缩后的文件对象
   */
  async function compressImageFile(file, maxDimension = 1920, quality = 0.82) {
    if (!file) return file;

    // 动图不通过 Canvas 重绘
    if (/\.gif$/i.test(file.name || "") || (file.type && file.type === "image/gif")) {
      return file;
    }

    // 检查是否为支持的位图类型
    const isImage =
      (file.type && /^image\/(jpe?g|png|webp|bmp)/i.test(file.type)) ||
      /\.(jpe?g|png|webp|bmp)$/i.test(file.name || "") ||
      !file.type;
    if (!isImage) {
      return file;
    }

    // 小于 150KB 且并非超大分辨率的文件无需重压缩
    if (file.size < 150 * 1024) {
      return file;
    }

    return new Promise((resolve) => {
      let objectUrl = null;
      try {
        objectUrl = URL.createObjectURL(file);
      } catch (e) {
        return resolve(file);
      }

      const img = new Image();
      img.onload = function () {
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        try {
          let width = img.naturalWidth || img.width;
          let height = img.naturalHeight || img.height;
          if (!width || !height) {
            return resolve(file);
          }

          const maxSide = Math.max(width, height);
          let targetWidth = width;
          let targetHeight = height;
          if (maxSide > maxDimension) {
            const ratio = maxDimension / maxSide;
            targetWidth = Math.max(1, Math.round(width * ratio));
            targetHeight = Math.max(1, Math.round(height * ratio));
          }

          const canvas = document.createElement("canvas");
          canvas.width = targetWidth;
          canvas.height = targetHeight;
          const ctx = canvas.getContext("2d");

          const isPng = file.type === "image/png" || /\.png$/i.test(file.name || "");
          if (isPng) {
            ctx.clearRect(0, 0, targetWidth, targetHeight);
          } else {
            ctx.fillStyle = "#ffffff";
            ctx.fillRect(0, 0, targetWidth, targetHeight);
          }

          ctx.drawImage(img, 0, 0, targetWidth, targetHeight);

          const outMime = isPng ? "image/png" : "image/jpeg";
          const ext = isPng ? ".png" : ".jpg";
          const rawBaseName = (file.name || "image").replace(/\.[^.]+$/, "");
          const outName = (rawBaseName || "image") + ext;

          canvas.toBlob(
            (blob) => {
              if (blob) {
                try {
                  const newFile = new File([blob], outName, {
                    type: outMime,
                    lastModified: Date.now(),
                  });
                  resolve(newFile);
                } catch (err) {
                  blob.name = outName;
                  resolve(blob);
                }
              } else {
                resolve(file);
              }
            },
            outMime,
            quality
          );
        } catch (procErr) {
          console.warn("Canvas 渲染处理异常:", procErr);
          resolve(file);
        }
      };

      img.onerror = function () {
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        resolve(file);
      };

      img.src = objectUrl;
    });
  }

  // 监听 input type="file" 的 change 事件
  $("#imageUpload").change(async function (e) {
    if (!e.target.files || e.target.files.length === 0) {
      return;
    }

    if (isUploading) {
      return;
    }

    currentImgLength = $("img.td").length;
    const files = Array.from(e.target.files);
    if (files.length > 9 - currentImgLength) {
      alert("最多只能上传9张图片，已上传" + currentImgLength + "张");
      $("#imageUpload").val("");
      return;
    }

    isUploading = true;
    const plusBtn = $(".new-plus");

    try {
      for (let i = 0; i < files.length; i++) {
        plusBtn.html(
          `<span class="text-xs text-[#07c160] font-medium text-center leading-tight">处理中<br>${i + 1}/${files.length}</span>`
        );
        const rawFile = files[i];
        let fileToUpload = rawFile;

        try {
          fileToUpload = await compressImageFile(rawFile);
        } catch (compErr) {
          console.warn("客户端图片压缩异常，降级原文件上传:", compErr);
          fileToUpload = rawFile;
        }

        plusBtn.html(
          `<span class="text-xs text-[#07c160] font-medium text-center leading-tight">上传中<br>${i + 1}/${files.length}</span>`
        );

        let fileUrl = await uploadFile(fileToUpload);
        if (fileUrl === false) {
          continue;
        }

        let attachCid = (fileUrl[1] && fileUrl[1].cid) ? fileUrl[1].cid : '';
        let imgUrl = fileUrl[0] || '';
        $("#sortgrid").append(`
          <div class="bg-[#f0f0f0] w-full aspect-square td relative" data-cid="${attachCid}" data-url="${imgUrl}">
            <input type="hidden" name="attachment[]" value="${attachCid}" class="attachment-input" />
            <img src="${imgUrl}" class="w-full h-full object-cover td" />
            <div class="absolute right-3 top-3">
              <img src="${xsvg}" class="w-[18px] h-[18px] cursor-pointer bg-[#f0f0f0] rounded-full btn-img-remove" />
            </div>
          </div>
        `);
        currentImgLength = $("img.td").length;
      }
    } finally {
      plusBtn.html(originalPlusHtml);
      isUploading = false;
      $("#imageUpload").val("");
    }

    resetPictures();
    bindRemoveEvent();
  });

  function bindRemoveEvent() {
    $(".btn-img-remove").off("click").on("click", function () {
      let item = $(this).closest(".td");
      let cid = item.find(".attachment-input").val() || item.data("cid") || "";
      let url = item.find("img.td").attr("src") || item.data("url") || "";

      // 立即移除 DOM 并重置图片列表字段
      item.remove();
      currentImgLength = $("img.td").length;
      resetPictures();

      // 异步通知后端删除已上传的文件及未挂载附件记录
      if (cid || url) {
        let delApi = window.WEBSITE_HOME_URL ? (window.WEBSITE_HOME_URL.replace(/\/$/, '') + '/api/delete-attachment') : '/api/delete-attachment';
        $.ajax({
          url: delApi,
          type: "POST",
          dataType: "json",
          data: { cid: cid, url: url },
          success: function (res) {
            console.log("附件删除成功:", res);
          },
          error: function (xhr) {
            console.warn("附件删除请求失败:", xhr);
          }
        });
      }
    });
  }
  bindRemoveEvent();

  async function uploadFile(file) {
    if (!file) return false;
    var formData = new FormData();
    var rawName = file.name || "image.jpg";
    var ext = (rawName.split('.').pop() || '').toLowerCase();
    if (ext === "jpeg") {
      rawName = rawName.replace(/\.jpeg$/i, ".jpg");
    }
    formData.append("file", file, rawName);

    var uploadUrl = $("#upload-url").val() || thisFormAction.replace(
      "/action/contents-post-edit",
      "/action/upload"
    );

    try {
      var response = await $.ajax({
        url: uploadUrl,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        timeout: 0, // 大视频上传可能要几分钟，交给服务器超时控制
      });

      if (!response || !Array.isArray(response) || !response[0]) {
        console.error("上传响应异常:", response);
        var rejected = (typeof response === "object" && response !== null && response.message)
          ? response.message
          : "";
        alert("上传失败，服务端返回异常数据" + (rejected ? "：" + rejected : "") +
            "\n常见原因：后台「设置→基本→允许上传的文件类型」未包含该扩展名");
        return false;
      }
      return response;
    } catch (xhr) {
      console.error("上传请求失败:", xhr, (xhr && xhr.responseText) || "");
      var msg = "上传失败";
      // 提取错误页正文（Typecho 错误页里 CSS 占大头，剥掉后异常信息才看得见）
      var body = (xhr && xhr.responseText ? String(xhr.responseText) : "")
        .replace(/<style[\s\S]*?<\/style>/gi, " ")
        .replace(/<script[\s\S]*?<\/script>/gi, " ")
        .replace(/<[^>]+>/g, " ")
        .replace(/\s+/g, " ")
        .trim();
      if (xhr.status === 403) {
        msg += ": 登录状态失效(403)，请刷新页面或重新登录后台";
      } else if (xhr.status === 413) {
        msg += ": 文件体积超出服务器限制(413)，请减小文件或调大 nginx client_max_body_size / PHP post_max_size";
      } else if (xhr.status === 0) {
        msg += ": 网络中断或连接被重置(0)";
      } else if (xhr.status >= 500) {
        msg += ": 服务器处理出错(" + xhr.status + ")";
      } else if (xhr.status > 0) {
        msg += ": HTTP " + xhr.status;
      } else {
        msg += ": 未知网络错误";
      }
      if (body) {
        msg += "\n---- 服务器返回(前300字) ----\n" + body.substring(0, 300);
      }
      alert(msg);
      return false;
    }
  }

  function resetPictures() {
    let picUrls = "";
    $("img.td").each(function (index, item) {
      let u = $(item).attr("src");
      if (u) picUrls += u + ",";
    });
    $("#friend_pictures").val(picUrls);
  }

  /**
   * 点击emoji
   */
  function clickEmoji() {
    $(".face-item").off("click");
    $(".face-item").on("click", function (e) {
      var input = $("#text");
      var textToAppend = $(e.target).text();
      var currentVal = input.val();
      input.val(currentVal + textToAppend);
    });
  }
  clickEmoji();

  function ClickEmojiBtn() {
    $(".face-btn").off("click");
    $(".face-btn").on("click", function () {
      if ($(".face-container").is(":hidden")) {
        $(".face-container").show();
      } else {
        $(".face-container").hide();
      }
    });
  }
  ClickEmojiBtn();

  /**
   * 2026-10-21：发布页工具栏「音乐 / 视频 / 文字」三个按钮统一为单选组。
   * 规则（对应"逻辑小问题"的修正）：
   *   1. 三者互斥，任意时刻恰有一个处于选中（高亮）状态；
   *   2. 音乐、视频都没选中时，文字编辑自动成为选中项（默认态）；
   *   3. 再次点击已选中的音乐/视频按钮 = 收起该面板并切回文字编辑，
   *      因此永远不会出现"三个按钮都不选中、页面又留着卡片"的状态。
   * 之前的问题：音乐按钮不取消视频高亮（两个可同时亮/两张卡同开）、
   * 视频按钮二次点击只取消自身高亮却不回退到文字模式（结果三个都不亮）。
   */
  var POST_EDITOR_MODE = "text";
  var MODE_CLASS_ON = "bg-[#e8f7ee] text-[#07c160] dark:bg-[#1e3a2b]";
  var MODE_CLASS_OFF = "bg-[#f0f0f0] text-gray-600 dark:bg-[#3a3a3a] dark:text-gray-300";
  var MODE_CONFIG = {
    text: { btn: "#text-mode-btn", card: "" },
    music: { btn: "#music-toggle-btn", card: "#music-publish-card" },
    video: { btn: "#video-toggle-btn", card: "#video-publish-card" }
  };

  function setPostEditorMode(mode) {
    if (!MODE_CONFIG.hasOwnProperty(mode)) mode = "text"; // 异常/空值一律回落到文字编辑
    POST_EDITOR_MODE = mode;

    $.each(MODE_CONFIG, function (key, conf) {
      var $btn = $(conf.btn);
      if ($btn.length) {
        if (key === mode) {
          $btn.addClass(MODE_CLASS_ON).removeClass(MODE_CLASS_OFF);
        } else {
          $btn.removeClass(MODE_CLASS_ON).addClass(MODE_CLASS_OFF);
        }
      }
      if (conf.card) {
        var $card = $(conf.card);
        if (key === mode) {
          $card.stop(true, true).removeClass("hidden").slideDown(200);
        } else if (!$card.hasClass("hidden")) {
          $card.stop(true, true).slideUp(200, function () { $(this).addClass("hidden"); });
        }
      }
    });

    // 展开任一插入面板时收起表情面板，避免两块面板同时占位
    if (mode !== "text") $(".face-container").hide();

    // 2026-10-26：音乐/视频模式下隐藏图片上传区（含已传图片预览），回文字模式恢复；
    // friend_pictures 隐藏字段不受影响，之前传过的图片发布时仍然有效
    var $imgArea = $("#image-upload-area");
    if ($imgArea.length) {
      if (mode === "text") {
        $imgArea.stop(true, true).slideDown(200);
      } else {
        $imgArea.stop(true, true).slideUp(200);
      }
    }
  }

  /**
   * 音乐面板控制与网易云/自定义音乐发布逻辑
   */
  function initMusicPublish() {
    function parseNeteaseId(str) {
      if (!str) return '';
      str = $.trim(str);
      let m = str.match(/id=(\d+)/i) || str.match(/song\/(\d+)/i) || str.match(/song\?id=(\d+)/i) || str.match(/^(\d+)$/) || str.match(/(\d{4,12})/);
      return m ? m[1] : '';
    }

    // 模式切换：点击音乐按钮（切到音乐模式；已选中时再点则收起并回文字模式）
    $("#music-toggle-btn").on("click", function () {
      setPostEditorMode(POST_EDITOR_MODE === "music" ? "text" : "music");
    });

    // 模式切换：点击文字发布按钮（默认模式，收起音乐/视频面板）
    $("#text-mode-btn").on("click", function () {
      setPostEditorMode("text");
    });

    // Tab 切换：网易云 ID
    $("#tab-netease-btn").on("click", function () {
      $(this).removeClass("border-transparent text-gray-500 dark:text-gray-400 bg-white/70 dark:bg-[#383838]")
             .addClass("border-[#07c160] text-[#07c160] bg-white dark:bg-[#353535]");
      $("#tab-custom-btn").removeClass("border-[#07c160] text-[#07c160] bg-white dark:bg-[#353535]")
                          .addClass("border-transparent text-gray-500 dark:text-gray-400 bg-white/70 dark:bg-[#383838]");
      $("#panel-netease").removeClass("hidden").show();
      $("#panel-custom").addClass("hidden").hide();
    });

    // Tab 切换：自定义音乐
    $("#tab-custom-btn").on("click", function () {
      $(this).removeClass("border-transparent text-gray-500 dark:text-gray-400 bg-white/70 dark:bg-[#383838]")
             .addClass("border-[#07c160] text-[#07c160] bg-white dark:bg-[#353535]");
      $("#tab-netease-btn").removeClass("border-[#07c160] text-[#07c160] bg-white dark:bg-[#353535]")
                           .addClass("border-transparent text-gray-500 dark:text-gray-400 bg-white/70 dark:bg-[#383838]");
      $("#panel-custom").removeClass("hidden").show();
      $("#panel-netease").addClass("hidden").hide();
    });

    // 获取并添加网易云音乐（外链立即生成，绝不失败；异步丰富歌名封面）
    $("#btn-fetch-netease").on("click", function () {
      let val = $.trim($("#netease-input").val());
      if (!val) {
        alert("请填写网易云歌曲 ID 或歌曲链接");
        return;
      }

      let songId = parseNeteaseId(val);
      if (!songId) {
        alert("未能识别出网易云歌曲ID，请输入纯数字ID（如 447925558）或歌曲链接");
        return;
      }

      // 1. 立即根据标准格式组装外链并填入自定义字段大文本框，确保 100% 成功生成
      let audioUrl = "https://music.163.com/song/media/outer/url?id=" + songId + ".mp3";
      let initialValue = `网易云歌曲 (${songId}) || 网易音乐人 || ${audioUrl} || `;
      $("#music").val(initialValue);
      $("#post-title").val(`[音乐分享]网易云歌曲 (${songId}) - 网易音乐人`);

      // 显示预览卡片
      $("#music-preview-img").attr("src", "").hide();
      $("#music-preview-title").text("网易云歌曲 (" + songId + ")");
      $("#music-preview-artist").text("网易音乐人");
      $("#music-preview-card").removeClass("hidden").css("display", "flex");

      // 2. 异步拉取歌名与封面图增强信息（采用与后台一致的高可靠性 $.ajax 请求）
      let btn = $(this);
      btn.text("获取中...");

      function getMusicApiUrl() {
        if (typeof window.SITE_API_MUSIC_URL !== "undefined" && window.SITE_API_MUSIC_URL) {
          return window.SITE_API_MUSIC_URL;
        }
        let el = document.getElementById("siteApiMusicUrl");
        if (el && el.value) {
          return el.value;
        }
        let homeEl = document.querySelector(".webSiteHomeUrl");
        if (homeEl && homeEl.value) {
          return homeEl.value.replace(/\/+$/, "") + "/api/music-info";
        }
        if (typeof globalData !== "undefined" && globalData.webSiteHomeUrl) {
          return globalData.webSiteHomeUrl.replace(/\/+$/, "") + "/api/music-info";
        }
        let loc = window.location;
        let path = loc.pathname;
        let base = loc.origin;
        if (path.indexOf("index.php") !== -1) {
          base += path.substring(0, path.indexOf("index.php") + 9);
        }
        return base.replace(/\/+$/, "") + "/api/music-info";
      }

      let siteApiUrl = getMusicApiUrl();

      function applyFetchedSong(song) {
        if (!song) return;
        let realValue = `${song.title} || ${song.artist} || ${song.url} || ${song.pic || ""}`;
        $("#music").val(realValue);

        if (song.pic) {
          $("#music-preview-img").attr("src", song.pic).removeClass("hidden").show();
        }
        $("#music-preview-title").text(song.title);
        $("#music-preview-artist").text(song.artist);

        // 自动设置文章标题为 [音乐分享]歌曲名 - 歌手
        let musicTitle = "[音乐分享]" + song.title + (song.artist ? " - " + song.artist : "");
        $("#post-title").val(musicTitle);
      }

      $.ajax({
        url: siteApiUrl,
        type: "POST",
        data: { song: songId },
        dataType: "json",
        timeout: 8000,
        success: function (res) {
          btn.text("获取并添加");
          if (res && res.status === 1 && res.data) {
            applyFetchedSong(res.data);
          }
        },
        error: function () {
          // POST 若受阻，自动回退到 GET 请求重试
          $.ajax({
            url: siteApiUrl + (siteApiUrl.indexOf("?") === -1 ? "?" : "&") + "song=" + encodeURIComponent(songId),
            type: "GET",
            dataType: "json",
            timeout: 5000,
            success: function (res) {
              btn.text("获取并添加");
              if (res && res.status === 1 && res.data) {
                applyFetchedSong(res.data);
              }
            },
            error: function () {
              btn.text("获取并添加");
            }
          });
        }
      });
    });

    // 清除网易云输入
    $("#btn-clear-netease").on("click", function () {
      $("#netease-input").val("");
      $("#music").val("");
      $("#music-preview-card").addClass("hidden").hide();
    });

    // 添加自定义音乐
    $("#btn-add-custom").on("click", function () {
      let url = $.trim($("#custom-url-input").val());
      let cover = $.trim($("#custom-cover-input").val());
      let title = $.trim($("#custom-title-input").val());
      let artist = $.trim($("#custom-artist-input").val()) || "未知歌手";

      if (!url) {
        alert("请填写音乐直链地址（如 mp3 链接）");
        return;
      }
      if (!title) {
        alert("请填写歌名");
        return;
      }

      let musicFieldValue = `${title} || ${artist} || ${url} || ${cover}`;
      $("#music").val(musicFieldValue);

      // 自动设置文章标题为 [音乐分享]歌曲名 - 歌手
      let musicTitle = "[音乐分享]" + title + (artist ? " - " + artist : "");
      $("#post-title").val(musicTitle);

      // 显示预览卡片
      if (cover) {
        $("#music-preview-img").attr("src", cover).show();
      } else {
        $("#music-preview-img").hide();
      }
      $("#music-preview-title").text(title);
      $("#music-preview-artist").text(artist);
      $("#music-preview-card").removeClass("hidden").css("display", "flex");
    });

    // 清除自定义音乐输入
    $("#btn-clear-custom").on("click", function () {
      $("#custom-url-input").val("");
      $("#custom-cover-input").val("");
      $("#custom-title-input").val("");
      $("#custom-artist-input").val("");
      $("#music").val("");
      $("#music-preview-card").addClass("hidden").hide();
      if ($("#post-title").val().indexOf("[音乐分享]") === 0) {
        $("#post-title").val("");
      }
    });

    // 移除已选择的音乐
    $("#btn-remove-selected-music").on("click", function () {
      $("#music").val("");
      $("#netease-input").val("");
      $("#music-preview-card").addClass("hidden").hide();
      if ($("#post-title").val().indexOf("[音乐分享]") === 0) {
        $("#post-title").val("");
      }
    });

    // 监听多行文本框 #music 的手动输入/粘贴，实时更新预览与标题
    $("#music").on("input propertychange change", function () {
      let val = $.trim($(this).val());
      if (val && val.indexOf("||") !== -1) {
        let parts = val.split("||").map(function (s) { return $.trim(s); });
        if (parts.length >= 2 && parts[0]) {
          $("#music-preview-title").text(parts[0]);
          $("#music-preview-artist").text(parts[1] || "未知歌手");
          if (parts[3]) {
            $("#music-preview-img").attr("src", parts[3]).show();
          } else {
            $("#music-preview-img").hide();
          }
          $("#music-preview-card").removeClass("hidden").css("display", "flex");

          let curT = $("#post-title").val();
          if (!curT || /^\d{4}-\d{2}-\d{2}/.test(curT) || curT.indexOf("[图片分享]") === 0 || curT.indexOf("[音乐分享]") === 0) {
            $("#post-title").val("[音乐分享]" + parts[0] + (parts[1] ? " - " + parts[1] : ""));
          }
          return;
        }
      }
      if (!val) {
        $("#music-preview-card").addClass("hidden").hide();
      }
    });

    // 如果文本框初始有值，回显预览
    let curVal = $.trim($("#music").val());
    if (curVal && curVal.indexOf("||") !== -1) {
      let parts = curVal.split("||").map(function (s) { return $.trim(s); });
      if (parts.length >= 3) {
        $("#music-preview-title").text(parts[0]);
        $("#music-preview-artist").text(parts[1] || "未知歌手");
        if (parts[3]) $("#music-preview-img").attr("src", parts[3]).show();
        $("#music-preview-card").removeClass("hidden").css("display", "flex");
      }
    }
  }
  initMusicPublish();

  // 初始化工具栏状态：默认文字编辑模式，并确保两张插入卡片处于收起状态
  setPostEditorMode("text");

  /**
   * 2026-10-21：发布框现代化
   *   1. 自动增高：两行起步随内容长高，超过 42vh 后内部滚动（Chrome 123+ 走 CSS field-sizing，其余用 JS 兜底）
   *   2. 字数统计：软阈值 2000，只做颜色提示，不拦截发布（后端无硬性限制，强行拦截会让内容白丢）
   *   3. Ctrl / ⌘ + Enter 直接发布，沿用表单原有提交按钮
   */
  function initComposerTextarea() {
    var $text = $("#text");
    if (!$text.length) return;

    var SOFT_LIMIT = 2000;
    var $count = $("#composer-count");
    var supportsFieldSizing = !!(window.CSS && CSS.supports && CSS.supports("field-sizing", "content"));

    function autoGrow() {
      if (supportsFieldSizing) return; // 交给浏览器，避免两套机制打架
      var el = $text[0];
      var maxPx = Math.round(window.innerHeight * 0.42);
      el.style.height = "auto";
      el.style.height = Math.min(el.scrollHeight, maxPx) + "px";
      el.style.overflowY = el.scrollHeight > maxPx ? "auto" : "hidden";
    }

    function updateCount() {
      var len = ($text.val() || "").replace(/\r\n/g, "\n").length;
      $count.text(len);
      $count.toggleClass("is-warn", len >= SOFT_LIMIT * 0.9 && len <= SOFT_LIMIT);
      $count.toggleClass("is-over", len > SOFT_LIMIT);
    }

    $text.on("input", function () {
      autoGrow();
      updateCount();
    });

    // 窗口尺寸变化（旋转屏幕/改窗口大小）后重新计算最大高度
    $(window).on("resize", autoGrow);

    // Ctrl / ⌘ + Enter 发布
    $text.on("keydown", function (e) {
      if ((e.ctrlKey || e.metaKey) && (e.key === "Enter" || e.keyCode === 13)) {
        e.preventDefault();
        var $form = $text.closest("form");
        var $submit = $form.find("button.btn-comment");
        if ($submit.length) {
          $submit.trigger("click");
        } else {
          $form.trigger("submit");
        }
      }
    });

    autoGrow();
    updateCount();
  }
  initComposerTextarea();

  $(".ad-container").click(function () {
    var adStatus = $(".ad-status").text();
    if (adStatus === "是") {
      $(".ad-status").text("否");
      $("#isAdvertise").val("0");
    } else {
      $(".ad-status").text("是");
      $("#isAdvertise").val("1");
    }
  });

  /**
   * 从朋友圈正文中智能提取简短标题（前15~20字）
   */
  function generateSmartTitle(text) {
    if (!text) return "";
    let t = text.replace(/<!--markdown-->/g, '')
                .replace(/!\[.*?\](?:\(.*?\)|\[.*?\])/g, '')
                .replace(/\[\d+\]:\s*https?:\/\/\S+/g, '')
                .replace(/https?:\/\/\S+/g, '')
                .replace(/<.*?>/g, '')
                .replace(/`.*?`/g, '')
                .replace(/[#*~_]/g, '');

    let lines = t.split(/\r?\n/).map(function (l) { return l.trim(); }).filter(function (l) { return l.length > 0; });
    if (lines.length === 0) return "";

    let firstLine = lines[0];
    let candidate = firstLine;
    if (firstLine.length <= 6 && lines.length > 1) {
      candidate = firstLine + " " + lines[1];
    }

    let chars = Array.from(candidate);
    if (chars.length > 20) {
      return chars.slice(0, 20).join("");
    }
    return candidate;
  }

  $('form[name="write_post"]').on("submit", function () {
    let text = $("#text").val() || "";
    let musicVal = $.trim($("#music").val() || "");
    let smartTitle = "";

    // 1. 若使用了网易云音乐链接或填写了音乐信息，文章标题优先生成为：[音乐分享]歌曲名 - 歌手
    if (musicVal) {
      let parts = musicVal.split("||").map(function (s) { return $.trim(s); });
      let songTitle = parts[0] || "";
      let artist = parts[1] || "";
      if (songTitle) {
        smartTitle = "[音乐分享]" + songTitle + (artist ? " - " + artist : "");
      }
    }

    // 2. 若无音乐，则从正文中智能提取标题
    if (!smartTitle) {
      smartTitle = generateSmartTitle(text);
    }

    if (smartTitle) {
      $("#post-title").val(smartTitle);
    } else {
      let now = new Date();
      let pad = function (n) { return n < 10 ? '0' + n : n; };
      let timeStr = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate()) + ' ' + pad(now.getHours()) + ':' + pad(now.getMinutes());
      $("#post-title").val("[图片分享] " + timeStr);
    }

    // 将上传的图片以 Markdown 语法追加到文章内容中（把图片添加到文章内容里面）
    let picMarkdown = "";
    $("img.td").each(function (index, item) {
      let u = $(item).attr("src");
      if (u) {
        let filename = u.substring(u.lastIndexOf('/') + 1);
        if (!text.includes(u) && (!filename || !text.includes(filename))) {
          picMarkdown += "\n\n![图片](" + u + ")";
        }
      }
    });
    if (picMarkdown) {
      $("#text").val(text ? (text + picMarkdown) : picMarkdown.trim());
    }
  });

/**
 * ============================================================
 * 视频发布（公共版：走 Typecho 原生 /action/upload 上传到网站服务器本地目录）
 * 两种方式二选一，最终都写入 friend_video 自定义字段（与后台「视频→朋友圈视频」同字段）：
 *   1. 本地上传：FormData 提交到 Typecho 原生上传接口，
 *      生成附件记录（attachment cid），可在后台「管理→附件」中管理
 *   2. 外部链接：MP4 / m3u8 直链
 * ============================================================
 */  var videoState = {
    url: "",
    cid: "",
    isUploading: false
  };

  var ALLOWED_EXT = ["mp4", "mov", "webm", "m4v"];
  var MAX_SIZE_MB = 200; // 与常见服务器上传上限对齐，超出时提前提示

  // ---------- 卡片开关与 Tab ----------
  // 视频按钮：切到视频模式；已选中时再点则收起并回文字模式（与音乐/文字共用状态机）
  $("#video-toggle-btn").on("click", function () {
    setPostEditorMode(POST_EDITOR_MODE === "video" ? "text" : "video");
  });

  $("#video-tab-upload-btn").on("click", function () {
    $(this).removeClass("border-transparent text-gray-500 dark:text-gray-400 bg-white/70 dark:bg-[#383838]")
           .addClass("border-[#07c160] text-[#07c160] bg-white dark:bg-[#353535]");
    $("#video-tab-link-btn").removeClass("border-[#07c160] text-[#07c160] bg-white dark:bg-[#353535]")
                            .addClass("border-transparent text-gray-500 dark:text-gray-400 bg-white/70 dark:bg-[#383838]");
    $("#video-panel-upload").removeClass("hidden").show();
    $("#video-panel-link").addClass("hidden").hide();
  });

  $("#video-tab-link-btn").on("click", function () {
    $(this).removeClass("border-transparent text-gray-500 dark:text-gray-400 bg-white/70 dark:bg-[#383838]")
           .addClass("border-[#07c160] text-[#07c160] bg-white dark:bg-[#353535]");
    $("#video-tab-upload-btn").removeClass("border-[#07c160] text-[#07c160] bg-white dark:bg-[#353535]")
                              .addClass("border-transparent text-gray-500 dark:text-gray-400 bg-white/70 dark:bg-[#383838]");
    $("#video-panel-link").removeClass("hidden").show();
    $("#video-panel-upload").addClass("hidden").hide();
  });

  // ---------- 写入 / 清除字段 ----------
  function setVideoField(url, cid) {
    videoState.url = url || "";
    videoState.cid = cid || "";
    $("#friend_video").val(videoState.url);
    // 挂载附件记录（与图片 attachment[] 同机制，便于后台管理）
    $("#video-attachment-cid").remove();
    if (videoState.cid) {
      $("<input>").attr({ type: "hidden", name: "attachment[]", value: videoState.cid, id: "video-attachment-cid" }).insertAfter("#friend_video");
    }
  }

  function showUploadPreview(url, filename) {
    $("#video-preview").removeClass("hidden");
    $("#video-preview-player").attr("src", url);
    $("#video-filename").text(filename || url).attr("title", filename || url);
    $("#video-upload-btn").addClass("hidden");
  }

  function hideUploadPreview() {
    $("#video-preview").addClass("hidden");
    $("#video-preview-player").removeAttr("src");
    $("#video-upload-btn").removeClass("hidden");
  }

  function clearAllVideo() {
    setVideoField("", "");
    hideUploadPreview();
    $("#videoLinkInput").val("");
    $("#video-link-preview").addClass("hidden");
    $("#video-link-player").removeAttr("src");
  }

  // ---------- Tab 1：本地上传（走 Typecho 原生 /action/upload，视频存网站服务器本地目录）----------
  // 带 XMLHttpRequest 进度回调的FormData上传，复用下方通用 uploadFile()
  function uploadFileWithProgress(file, onProgress) {
    var formData = new FormData();
    var rawName = file.name || "video.mp4";
    var ext = (rawName.split('.').pop() || '').toLowerCase();
    if (ext === "jpeg") {
      rawName = rawName.replace(/\.jpeg$/i, ".jpg");
    }
    formData.append("file", file, rawName);

    var uploadUrl = $("#upload-url").val() || thisFormAction.replace(
      "/action/contents-post-edit",
      "/action/upload"
    );

    return new Promise(function (resolve, reject) {
      $.ajax({
        url: uploadUrl,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        timeout: 0,
        xhr: function () {
          var xhr = $.ajaxSettings.xhr();
          if (xhr.upload && onProgress) {
            xhr.upload.addEventListener("progress", function (ev) {
              if (ev.lengthComputable) {
                onProgress(Math.round((ev.loaded / ev.total) * 100));
              }
            });
          }
          return xhr;
        },
        success: function (response) {
          resolve(response);
        },
        error: function (xhr) {
          reject(xhr);
        }
      });
    });
  }

  $("#video-upload-btn").on("click", function () {
    if (videoState.isUploading) return;
    $("#videoUpload").click();
  });

  $("#videoUpload").on("change", async function (e) {
    if (!e.target.files || e.target.files.length === 0) return;
    if (videoState.isUploading) return;

    var file = e.target.files[0];
    var ext = ((file.name || "").split(".").pop() || "").toLowerCase();

    if (ALLOWED_EXT.indexOf(ext) === -1) {
      alert("暂不支持 ." + ext + " 格式，请上传 MP4 / MOV / WebM 视频");
      $("#videoUpload").val("");
      return;
    }
    if (file.size > MAX_SIZE_MB * 1024 * 1024) {
      alert("视频超过 " + MAX_SIZE_MB + "MB，请压缩后再试，或改用「视频链接」方式");
      $("#videoUpload").val("");
      return;
    }

    videoState.isUploading = true;
    var btn = $("#video-upload-btn");
    btn.text("上传中 0%");

    try {
      // 走 Typecho 原生上传接口，视频存入网站服务器本地 usr/uploads/，并生成附件记录
      var res;
      try {
        res = await uploadFileWithProgress(file, function (pct) {
          btn.text("上传中 " + pct + "%");
        });
      } catch (xhr) {
        console.error("视频上传请求失败:", xhr, (xhr && xhr.responseText) || "");
        var msg = "上传失败";
        var body = (xhr && xhr.responseText ? String(xhr.responseText) : "")
          .replace(/<style[\s\S]*?<\/style>/gi, " ")
          .replace(/<script[\s\S]*?<\/script>/gi, " ")
          .replace(/<[^>]+>/g, " ")
          .replace(/\s+/g, " ")
          .trim();
        if (xhr && xhr.status === 403) {
          msg += ": 登录状态失效(403)，请刷新页面或重新登录后台";
        } else if (xhr && xhr.status === 413) {
          msg += ": 文件体积超出服务器限制(413)，请减小文件或调大 nginx client_max_body_size / PHP post_max_size";
        } else if (xhr && xhr.status === 0) {
          msg += ": 网络中断或连接被重置(0)";
        } else if (xhr && xhr.status >= 500) {
          msg += ": 服务器错误(" + xhr.status + ")" + (body ? "，" + body.slice(0, 200) : "");
        } else if (body) {
          msg += ": " + body.slice(0, 200);
        }
        alert(msg);
        btn.text("上传失败，点击重试");
        return;
      }
      if (!res || !Array.isArray(res) || !res[0]) {
        console.error("上传响应异常:", res);
        var rejected = (typeof res === "object" && res !== null && res.message)
          ? res.message
          : "";
        alert("上传失败，服务端返回异常数据" + (rejected ? "：" + rejected : "") +
            "\n常见原因：后台「设置→基本→允许上传的文件类型」未包含该扩展名");
        btn.text("上传失败，点击重试");
        return;
      }
      var fileUrl = res[0] || "";
      var attachCid = (res[1] && res[1].cid) ? res[1].cid : "";
      if (!fileUrl) {
        alert("上传返回异常：未取得视频地址");
        btn.text("点击选择本地视频");
        return;
      }
      setVideoField(fileUrl, attachCid);
      showUploadPreview(fileUrl, file.name);
      btn.text("已上传，点击可更换视频");
    } finally {
      videoState.isUploading = false;
      $("#videoUpload").val("");
    }
  });

  $("#btn-remove-video").on("click", function () {
    var cid = videoState.cid;
    var url = videoState.url;
    setVideoField("", "");
    hideUploadPreview();
    $("#video-upload-btn").text("点击选择本地视频");

    // 与图片删除一致：异步通知后端删除附件
    if (cid || url) {
      var delApi = window.WEBSITE_HOME_URL ? (window.WEBSITE_HOME_URL.replace(/\/$/, "") + "/api/delete-attachment") : "/api/delete-attachment";
      $.ajax({
        url: delApi,
        type: "POST",
        dataType: "json",
        data: { cid: cid, url: url },
        success: function (res) { console.log("视频附件删除成功:", res); },
        error: function (xhr) { console.warn("视频附件删除请求失败:", xhr); }
      });
    }
  });

  // ---------- Tab 2：外部链接 ----------
  $("#videoLinkInput").on("change blur", function () {
    var url = $.trim($(this).val());
    if (!url) {
      // 清空链接输入时，若没有本地上传的视频，同步清空字段
      if (!videoState.cid) setVideoField("", "");
      $("#video-link-preview").addClass("hidden");
      $("#video-link-player").removeAttr("src");
      return;
    }
    if (!/^https?:\/\//i.test(url)) {
      return; // 输入到一半不打扰，发布前有校验
    }
    setVideoField(url, "");
    // 本地预览：mp4 可以直接预览；m3u8 浏览器原生不支持，就不做预览
    if (/\.m3u8(\?|$)/i.test(url)) {
      $("#video-link-preview").addClass("hidden");
    } else {
      $("#video-link-preview").removeClass("hidden");
      $("#video-link-player").attr("src", url);
    }
  });

  // ---------- 发布前校验 ----------
  $("form[name=write_post]").on("submit", function () {
    var linkVal = $.trim($("#videoLinkInput").val() || "");
    if (linkVal && !/^https?:\/\//i.test(linkVal)) {
      alert("视频链接格式不正确，请以 http(s):// 开头");
      $("#videoLinkInput").focus();
      return false;
    }
    return true;
  });
});
