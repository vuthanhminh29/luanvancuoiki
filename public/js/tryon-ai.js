(function () {
    const root = document.querySelector("[data-tryon-app]");
    if (!root) return;
    const jeelizBasePath = root.dataset.jeelizBasePath || `${window.location.origin}/vendor/jeelizGlassesVTOWidget`;
    const jeelizScriptUrl = root.dataset.jeelizScriptUrl || `${jeelizBasePath}/dist/JeelizVTOWidget.js`;
    const snapshotStoreUrl = root.dataset.snapshotStoreUrl || "";
    const loginUrl = root.dataset.loginUrl || "/dang-nhap";
    const canStoreSnapshot = root.dataset.authenticated === "true";

    const productDataNode = document.getElementById("tryonProductData");
    const products = JSON.parse(productDataNode?.textContent || "[]");

    const startButton = document.getElementById("tryonStartCamera");
    const miniCameraButton = document.getElementById("tryonCameraMiniBtn");
    const cameraToggleText = document.getElementById("tryonCameraToggleText");
    const uploadButton = document.getElementById("tryonUploadImage");
    const uploadPanel = document.getElementById("tryonUploadPanel");
    const uploadBack = document.getElementById("tryonUploadBack");
    const uploadCancel = document.getElementById("tryonUploadCancel");
    const uploadInput = document.getElementById("tryonImageInput");
    const uploadDropzone = document.getElementById("tryonUploadDropzone");
    const uploadError = document.getElementById("tryonUploadError");
    const uploadedPreview = document.getElementById("tryonUploadedPreview");
    const productList = document.getElementById("tryonProductList");
    const statusNode = document.getElementById("tryonStatus");
    const noModelNode = document.getElementById("tryonNoModel");
    const selectedImage = document.getElementById("tryonSelectedImage");
    const selectedName = document.getElementById("tryonSelectedName");
    const selectedPrice = document.getElementById("tryonSelectedPrice");
    const selectedDesc = document.getElementById("tryonSelectedDesc");
    const selectedBrand = document.getElementById("tryonSelectedBrand");
    const selectedMaterial = document.getElementById("tryonSelectedMaterial");
    const detailLink = document.getElementById("tryonDetailLink");
    const closeLink = document.getElementById("tryonCloseLink");
    const cartVariantId = document.getElementById("tryonCartVariantId");
    const cartProductId = document.getElementById("tryonCartProductId");
    const cartName = document.getElementById("tryonCartName");
    const cartImage = document.getElementById("tryonCartImage");
    const cartPrice = document.getElementById("tryonCartPrice");
    const snapshotButton = document.getElementById("tryonSaveSnapshot");
    const widgetNode = document.getElementById("JeelizVTOWidget");
    const canvasNode = document.getElementById("JeelizVTOWidgetCanvas");
    const isImmersivePage = root.classList.contains("tryon-ai-page--immersive");
    const isRaybanStyle = isImmersivePage ||
        root.classList.contains("tryon-ai-modal--rayban") ||
        root.classList.contains("tryon-ai-modal--eyebuy");

    if (root.classList.contains("tryon-ai-fullscreen")) {
        document.body.classList.add("tryon-ai-lock");
    }

    let activeProduct = products[0] || null;
    let isStarted = false;
    let isReady = false;
    let pendingProduct = null;
    let tryonMode = "idle";
    let activeCameraStream = null;
    let manualGlassesYOffset = 0;
    let currentImageObjectUrl = null;

    function setCameraToggle(isOn) {
        if (startButton) {
            startButton.classList.toggle("is-active", isOn);
            startButton.setAttribute("aria-pressed", isOn ? "true" : "false");
        }
        if (miniCameraButton) miniCameraButton.classList.toggle("is-active", isOn);
        if (cameraToggleText) cameraToggleText.textContent = isOn ? "Tắt camera" : "Bật camera";
    }

    function sanitizeStatusMessage(message) {
        let text = `${message ?? ""}`;
        const fallback = activeProduct?.name || "kính đang chọn";

        products.forEach((product) => {
            if (product?.sku) text = text.split(product.sku).join(product.name || fallback);
        });

        text = text.replace(/\bm\u00e3\s+[a-z0-9][a-z0-9_-]*/gi, fallback);
        text = text.replace(/[a-z0-9]+(?:_[a-z0-9]+){1,}/gi, fallback);
        text = text.replace(/\bm\u00e3\s+(?=\S)/gi, "");

        return text.replace(/\s{2,}/g, " ").trim();
    }

    function setStatus(message) {
        if (statusNode) statusNode.textContent = sanitizeStatusMessage(message);
    }

    function setSnapshotLoading(isLoading) {
        if (!snapshotButton) return;

        snapshotButton.disabled = isLoading || !activeProduct?.hasModel;
        snapshotButton.classList.toggle("is-loading", isLoading);
        snapshotButton.innerHTML = isLoading
            ? '<i class="fas fa-spinner fa-spin"></i> Đang lưu...'
            : '<i class="fas fa-camera-retro"></i> Chụp/Lưu kết quả';
    }

    function showInlineError(message) {
        if (noModelNode) noModelNode.textContent = message;
        root.classList.add("tryon-no-model-active");
        root.classList.remove("tryon-is-loading");
        setStatus(message);
    }

    function setUploadError(message) {
        if (uploadError) uploadError.textContent = message || "";
    }

    function resizeWidget(delay = 0) {
        window.setTimeout(() => {
            if (window.JEELIZVTOWIDGET && typeof JEELIZVTOWIDGET.resize === "function") {
                JEELIZVTOWIDGET.resize();
            }
        }, delay);
    }

    let jeelizLoaderPromise = null;

    function ensureJeelizWidget() {
        if (window.JEELIZVTOWIDGET) return Promise.resolve();
        if (jeelizLoaderPromise) return jeelizLoaderPromise;

        jeelizLoaderPromise = new Promise((resolve, reject) => {
            const script = document.createElement("script");
            const separator = jeelizScriptUrl.includes("?") ? "&" : "?";

            script.src = `${jeelizScriptUrl}${separator}retry=${Date.now()}`;
            script.async = true;
            script.onload = () => {
                window.JEELIZVTOWIDGET ? resolve() : reject(new Error("JEELIZ_WIDGET_MISSING"));
            };
            script.onerror = () => reject(new Error("JEELIZ_SCRIPT_LOAD_FAILED"));
            document.head.appendChild(script);
        }).catch((error) => {
            jeelizLoaderPromise = null;
            throw error;
        });

        return jeelizLoaderPromise;
    }

    function fitCanvasToWidget(delay = 0) {
        window.setTimeout(() => {
            if (!widgetNode || !canvasNode) return;
            const rect = widgetNode.getBoundingClientRect();
            if (!rect.width || !rect.height) return;
            canvasNode.style.width = `${Math.round(rect.width)}px`;
            canvasNode.style.height = `${Math.round(rect.height)}px`;
            canvasNode.style.top = "0";
            canvasNode.style.left = "50%";
            canvasNode.style.right = "auto";
            canvasNode.style.transform = "translateX(-50%)";
        }, delay);
    }

    function fitCanvasToImage(image, delay = 0) {
        window.setTimeout(() => {
            if (!widgetNode || !canvasNode || !image?.width || !image?.height) return;
            const rect = widgetNode.getBoundingClientRect();
            if (!rect.width || !rect.height) return;

            // Calculate the display size using object-fit:contain logic
            const imageRatio = image.width / image.height;
            const widgetRatio = rect.width / rect.height;
            let displayW, displayH;

            if (widgetRatio > imageRatio) {
                // Widget is wider than image: height fills, width is proportional
                displayH = rect.height;
                displayW = displayH * imageRatio;
            } else {
                // Widget is taller than image: width fills, height is proportional
                displayW = rect.width;
                displayH = displayW / imageRatio;
            }

            canvasNode.style.position = "absolute";
            canvasNode.style.width = `${Math.round(displayW)}px`;
            canvasNode.style.height = `${Math.round(displayH)}px`;
            canvasNode.style.top = "50%";
            canvasNode.style.left = "50%";
            canvasNode.style.right = "auto";
            canvasNode.style.bottom = "auto";
            canvasNode.style.transform = "translate(-50%, -50%)";
            canvasNode.style.maxWidth = "100%";
            canvasNode.style.maxHeight = "100%";

            // Also sync the preview image position/size if it exists
            if (uploadedPreview && uploadedPreview.src) {
                uploadedPreview.style.width = `${Math.round(displayW)}px`;
                uploadedPreview.style.height = `${Math.round(displayH)}px`;
                uploadedPreview.style.inset = "auto";
                uploadedPreview.style.top = "50%";
                uploadedPreview.style.left = "50%";
                uploadedPreview.style.transform = "translate(-50%, -50%)";
                uploadedPreview.style.objectFit = "fill";
            }
        }, delay);
    }

    function escapeHtml(value) {
        return `${value ?? ""}`
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function noModelMessage(product) {
        const name = product?.name ? ` "${product.name}"` : "";
        return `Xin l\u1ed7i k\u00ednh${name} hi\u1ec7n t\u1ea1i ch\u01b0a c\u00f3 ki\u1ec3m th\u1eed.`;
    }

    function setNoModel(product) {
        root.classList.add("tryon-no-model-active");
        root.classList.remove("tryon-is-loading");
        const message = noModelMessage(product);
        if (noModelNode) noModelNode.textContent = message;
        setStatus(message);
    }

    function clearNoModel() {
        root.classList.remove("tryon-no-model-active");
        if (noModelNode) noModelNode.textContent = "";
    }

    function applyGlassesOffset() {
        if (window.JEELIZVTO && typeof JEELIZVTO.set_offset === "function") {
            try {
                JEELIZVTO.set_offset([0, manualGlassesYOffset]);
            } catch (error) {
                console.error(error);
            }
        }
    }

    function rememberCameraStream(source) {
        if (source && typeof source.getTracks === "function") {
            activeCameraStream = source;
            return;
        }
        if (source && source.srcObject && typeof source.srcObject.getTracks === "function") {
            activeCameraStream = source.srcObject;
            return;
        }
        if (source && source.videoStream && typeof source.videoStream.getTracks === "function") {
            activeCameraStream = source.videoStream;
            return;
        }
        activeCameraStream = null;
    }

    function stopCameraStream() {
        if (activeCameraStream && typeof activeCameraStream.getTracks === "function") {
            activeCameraStream.getTracks().forEach((track) => {
                try {
                    track.stop();
                } catch (error) {
                    console.error(error);
                }
            });
            activeCameraStream = null;
        }

        document.querySelectorAll("video").forEach((video) => {
            const stream = video.srcObject || video.videoStream;
            if (stream && typeof stream.getTracks === "function") {
                stream.getTracks().forEach((track) => {
                    try {
                        track.stop();
                    } catch (error) {
                        console.error(error);
                    }
                });
            }
            try {
                video.pause();
                video.srcObject = null;
                video.removeAttribute("src");
                video.load && video.load();
            } catch (error) {
                console.error(error);
            }
        });
    }

    function stopTryon(options = {}) {
        pendingProduct = null;
        root.classList.remove("tryon-is-loading");
        root.classList.remove("tryon-image-mode", "tryon-upload-active");
        if (uploadButton) uploadButton.classList.remove("is-active");
        setCameraToggle(false);
        setUploadError("");
        if (uploadInput) uploadInput.value = "";
        if (currentImageObjectUrl) {
            URL.revokeObjectURL(currentImageObjectUrl);
            currentImageObjectUrl = null;
        }
        if (uploadedPreview) uploadedPreview.removeAttribute("src");

        if (!window.JEELIZVTOWIDGET || typeof JEELIZVTOWIDGET.destroy !== "function" || (!isStarted && !isReady)) {
            isStarted = false;
            isReady = false;
            tryonMode = "idle";
            root.classList.remove("tryon-camera-active");
            stopCameraStream();
            return Promise.resolve();
        }

        return Promise.resolve(JEELIZVTOWIDGET.destroy())
            .catch(() => {})
            .finally(() => {
                isStarted = false;
                isReady = false;
                tryonMode = "idle";
                root.classList.remove("tryon-camera-active");
                if (canvasNode) {
                    const context = canvasNode.getContext && canvasNode.getContext("2d");
                    if (context) context.clearRect(0, 0, canvasNode.width, canvasNode.height);
                }
                stopCameraStream();
            });
    }

    function setSelectedProduct(product) {
        activeProduct = product;

        if (selectedImage) selectedImage.src = product?.productImage || "";
        if (selectedName) selectedName.textContent = product?.name || "Ch\u01b0a ch\u1ecdn k\u00ednh";
        if (selectedPrice) selectedPrice.textContent = product?.priceText || "";
        if (selectedDesc) selectedDesc.textContent = product?.description || "";
        if (selectedBrand) selectedBrand.textContent = product?.brand || "";
        if (selectedMaterial) selectedMaterial.textContent = product?.material || "";
        if (detailLink && product?.detailUrl) detailLink.href = product.detailUrl;
        if (closeLink && product?.detailUrl) closeLink.href = product.detailUrl;
        if (cartVariantId) cartVariantId.value = product?.variantId || "";
        if (cartProductId) cartProductId.value = product?.id || "";
        if (cartName) cartName.value = product?.name || "";
        if (cartImage) cartImage.value = product?.cartImage || "";
        if (cartPrice) cartPrice.value = product?.price || "";

        if (snapshotButton) {
            const canSnapshot = !!product?.hasModel;
            snapshotButton.disabled = !canSnapshot;
            snapshotButton.title = canSnapshot
                ? "Chụp và lưu ảnh thử kính"
                : "Sản phẩm chưa có model thử kính";
        }

        document.querySelectorAll(".tryon-ai-product").forEach((button) => {
            button.classList.toggle("is-active", button.dataset.sku === product?.sku);
        });
    }

    function loadProduct(product) {
        if (!product) {
            setStatus("Ch\u01b0a c\u00f3 s\u1ea3n ph\u1ea9m \u0111\u1ec3 th\u1eed k\u00ednh.");
            return;
        }

        if (tryonMode === "image") {
            stopTryon().then(() => loadProduct(product));
            return;
        }

        setSelectedProduct(product);

        if (!product.hasModel) {
            setCameraToggle(false);
            setNoModel(product);
            return;
        }

        clearNoModel();

        if (!window.JEELIZVTOWIDGET) {
            root.classList.add("tryon-is-loading");
            setStatus("\u0110ang t\u1ea3i th\u01b0 vi\u1ec7n th\u1eed k\u00ednh 3D...");
            ensureJeelizWidget()
                .then(() => {
                    root.classList.remove("tryon-is-loading");
                    loadProduct(product);
                })
                .catch(() => {
                    root.classList.remove("tryon-is-loading");
                    setCameraToggle(false);
                    setStatus("Kh\u00f4ng t\u1ea3i \u0111\u01b0\u1ee3c th\u01b0 vi\u1ec7n th\u1eed k\u00ednh 3D. Vui l\u00f2ng ki\u1ec3m tra file vendor/jeelizGlassesVTOWidget/dist/JeelizVTOWidget.js.");
                });
            return;
        }

        root.classList.add("tryon-is-loading");
        setStatus("\u0110ang t\u1ea3i k\u00ednh \u0111\u00e3 ch\u1ecdn...");

            if (!isStarted) {
                isStarted = true;
                tryonMode = "camera";
                root.classList.add("tryon-camera-active");
            const startPromise = JEELIZVTOWIDGET.start({
                placeHolder: widgetNode,
                canvas: canvasNode,
                isShadow: true,
                sku: product.sku,
                searchImageMask: `${jeelizBasePath}/images/target512.jpg`,
                searchImageColor: 0xeeeeee,
                onWebcamGet: rememberCameraStream,
                callbackReady: function () {
                    isReady = true;
                    setCameraToggle(true);
                    root.classList.remove("tryon-is-loading");
                    resizeWidget();
                    resizeWidget(450);
                    fitCanvasToWidget();
                    fitCanvasToWidget(520);
                    setStatus("\u0110\u01b0a m\u1eb7t v\u00e0o gi\u1eefa khung \u0111\u1ec3 th\u1eed k\u00ednh.");

                    if (pendingProduct && pendingProduct.sku !== product.sku) {
                        const nextProduct = pendingProduct;
                        pendingProduct = null;
                        loadProduct(nextProduct);
                    }
                },
                onError: function (errorLabel) {
                    root.classList.remove("tryon-is-loading");
                    if (errorLabel === "INVALID_SKU") {
                        if (!isReady) {
                            isStarted = false;
                            tryonMode = "idle";
                            pendingProduct = null;
                        }
                        setCameraToggle(false);
                        setNoModel(activeProduct);
                        return;
                    }
                    if (errorLabel === "WEBCAM_UNAVAILABLE") {
                        if (!isReady) {
                            isStarted = false;
                            tryonMode = "idle";
                            pendingProduct = null;
                        }
                        setCameraToggle(false);
                        setStatus("Kh\u00f4ng m\u1edf \u0111\u01b0\u1ee3c camera. Vui l\u00f2ng c\u1ea5p quy\u1ec1n camera tr\u00ean tr\u00ecnh duy\u1ec7t.");
                        return;
                    }
                    if (!isReady) {
                        isStarted = false;
                        tryonMode = "idle";
                        pendingProduct = null;
                    }
                    setCameraToggle(false);
                    setStatus(`Kh\u00f4ng th\u1ec3 m\u1edf th\u1eed k\u00ednh 3D (${errorLabel}).`);
                },
            });
            if (startPromise && typeof startPromise.catch === "function") {
                startPromise.catch((errorLabel) => {
                    isStarted = false;
                    tryonMode = "idle";
                    root.classList.remove("tryon-is-loading");
                    setCameraToggle(false);
                    setStatus(`Kh\u00f4ng th\u1ec3 kh\u1edfi \u0111\u1ed9ng th\u1eed k\u00ednh 3D (${errorLabel}).`);
                });
            }
            return;
        }

        if (!isReady) {
            pendingProduct = product;
            return;
        }

        try {
            JEELIZVTOWIDGET.load(product.sku);
            resizeWidget();
            resizeWidget(450);
            fitCanvasToWidget();
            fitCanvasToWidget(520);
            root.classList.remove("tryon-is-loading");
            setCameraToggle(true);
            setStatus("\u0110ang th\u1eed k\u00ednh \u0111\u00e3 ch\u1ecdn.");
        } catch (error) {
            root.classList.remove("tryon-is-loading");
            setCameraToggle(false);
            setNoModel(product);
            console.error(error);
        }
    }

    function renderProducts() {
        if (!productList) return;

        if (!products.length) {
            productList.innerHTML = "<p>Ch\u01b0a c\u00f3 s\u1ea3n ph\u1ea9m \u0111\u1ec3 th\u1eed k\u00ednh.</p>";
            return;
        }

        productList.innerHTML = products.map((product) => {
            if (isRaybanStyle) {
                return `
                    <button type="button" class="tryon-ai-product${product.hasModel ? "" : " is-unavailable"}" data-sku="${escapeHtml(product.sku)}" title="${escapeHtml(product.name)}">
                        <img src="${escapeHtml(product.productImage)}" alt="">
                    </button>
                `;
            }

            return `
                <button type="button" class="tryon-ai-product${product.hasModel ? "" : " is-unavailable"}" data-sku="${escapeHtml(product.sku)}">
                    <img src="${escapeHtml(product.productImage)}" alt="">
                    <strong>${escapeHtml(product.name)}</strong>
                    <span>${escapeHtml(product.priceText)}</span>
                    <small></small>
                </button>
            `;
        }).join("");

        productList.addEventListener("click", (event) => {
            const button = event.target.closest(".tryon-ai-product");
            if (!button) return;
            const product = products.find((item) => item.sku === button.dataset.sku);
            if (!product) return;
            setSelectedProduct(product);
            loadProduct(product);
        });
    }

    function openUploadPanel() {
        setUploadError("");
        root.classList.add("tryon-upload-active");
        if (uploadButton) uploadButton.classList.add("is-active");
        setCameraToggle(false);
        if (uploadInput) uploadInput.value = "";
    }

    function closeUploadPanel() {
        root.classList.remove("tryon-upload-active");
        setUploadError("");
        if (uploadInput) uploadInput.value = "";
        if (tryonMode !== "image") {
            if (uploadButton) uploadButton.classList.remove("is-active");
            setCameraToggle(tryonMode === "camera" && (isStarted || isReady));
        }
    }

    // Crop image to face region using Canvas + face detection API
    function cropToFace(image) {
        return new Promise(function (resolve) {
            // Use FaceDetector API if available (Chrome/Edge)
            if (window.FaceDetector) {
                var detector = new window.FaceDetector({ fastMode: false });
                detector.detect(image).then(function (faces) {
                    if (!faces || faces.length === 0) { resolve(image); return; }
                    var face = faces[0].boundingBox;
                    // Expand crop area by 80% around face for glasses context
                    var padX = face.width * 0.8;
                    var padY = face.height * 0.8;
                    var sx = Math.max(0, face.x - padX);
                    var sy = Math.max(0, face.y - padY);
                    var sw = Math.min(image.width - sx, face.width + padX * 2);
                    var sh = Math.min(image.height - sy, face.height + padY * 2);
                    var crop = document.createElement("canvas");
                    crop.width = Math.round(sw);
                    crop.height = Math.round(sh);
                    crop.getContext("2d").drawImage(image, sx, sy, sw, sh, 0, 0, crop.width, crop.height);
                    var croppedImg = new Image();
                    croppedImg.onload = function () { resolve(croppedImg); };
                    croppedImg.onerror = function () { resolve(image); };
                    croppedImg.src = crop.toDataURL("image/jpeg", 0.95);
                }).catch(function () { resolve(image); });
                return;
            }
            // Fallback: if image is portrait and face likely in top portion, crop top 55%
            // This handles full-body photos where face is small
            var ratio = image.width / image.height;
            if (ratio < 0.85) {
                // Portrait photo - crop to top 55% to focus on face area
                var cropH = Math.round(image.height * 0.55);
                var fallbackCrop = document.createElement("canvas");
                fallbackCrop.width = image.width;
                fallbackCrop.height = cropH;
                fallbackCrop.getContext("2d").drawImage(image, 0, 0, image.width, cropH, 0, 0, image.width, cropH);
                var fallbackImg = new Image();
                fallbackImg.onload = function () { resolve(fallbackImg); };
                fallbackImg.onerror = function () { resolve(image); };
                fallbackImg.src = fallbackCrop.toDataURL("image/jpeg", 0.95);
                return;
            }
            resolve(image);
        });
    }

    function readImageFile(file) {
        if (!file) return;
        if (!file.type || !file.type.startsWith("image/")) {
            setUploadError("Vui l\u00f2ng ch\u1ecdn file \u1ea3nh.");
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            setUploadError("\u1ea2nh t\u1ed1i \u0111a 5 MB.");
            return;
        }

        const image = new Image();
        const objectUrl = URL.createObjectURL(file);
        image.onload = function () {
            closeUploadPanel();
            setStatus("\u0110ang x\u1eed l\u00fd \u1ea3nh...");
            cropToFace(image).then(function (processedImage) {
                // Pass both: processedImage for Jeeliz detection, image as originalImage for preview layout
                startImageTryon(processedImage, objectUrl, image);
            });
        };
        image.onerror = function () {
            URL.revokeObjectURL(objectUrl);
            setUploadError("Kh\u00f4ng \u0111\u1ecdc \u0111\u01b0\u1ee3c \u1ea3nh n\u00e0y.");
        };
        image.src = objectUrl;
    }

    function startImageTryon(image, objectUrl, originalImage) {
        originalImage = originalImage || image;
        if (!activeProduct) {
            URL.revokeObjectURL(objectUrl);
            setStatus("Ch\u01b0a c\u00f3 s\u1ea3n ph\u1ea9m \u0111\u1ec3 th\u1eed k\u00ednh.");
            return;
        }

        setSelectedProduct(activeProduct);

        if (!activeProduct.hasModel) {
            URL.revokeObjectURL(objectUrl);
            setNoModel(activeProduct);
            return;
        }

        if (!window.JEELIZVTOWIDGET || !window.JEELIZVTO || typeof JEELIZVTO.process_image !== "function") {
            setStatus("\u0110ang t\u1ea3i th\u01b0 vi\u1ec7n th\u1eed k\u00ednh 3D...");
            ensureJeelizWidget()
                .then(() => {
                    if (!window.JEELIZVTO || typeof JEELIZVTO.process_image !== "function") {
                        URL.revokeObjectURL(objectUrl);
                        setStatus("Th\u01b0 vi\u1ec7n th\u1eed k\u00ednh ch\u01b0a h\u1ed7 tr\u1ee3 x\u1eed l\u00fd \u1ea3nh.");
                        return;
                    }
                    startImageTryon(image, objectUrl, originalImage);
                })
                .catch(() => {
                    URL.revokeObjectURL(objectUrl);
                    setStatus("Kh\u00f4ng t\u1ea3i \u0111\u01b0\u1ee3c th\u01b0 vi\u1ec7n th\u1eed k\u00ednh 3D. Vui l\u00f2ng ki\u1ec3m tra file vendor/jeelizGlassesVTOWidget/dist/JeelizVTOWidget.js.");
                });
            return;
        }

        const run = () => {
            currentImageObjectUrl = objectUrl;
            const layoutImage = originalImage; // full photo for canvas layout
            // Show the original photo as background layer
            if (uploadedPreview) {
                uploadedPreview.src = objectUrl;
            }
            root.classList.add("tryon-image-mode", "tryon-is-loading");
            root.classList.remove("tryon-no-model-active");
            if (uploadButton) uploadButton.classList.add("is-active");
            setCameraToggle(false);
            tryonMode = "image";
            isStarted = true;
            root.classList.add("tryon-camera-active");
            isReady = false;
            setStatus("\u0110ang nh\u1eadn di\u1ec7n khu\u00f4n m\u1eb7t t\u1eeb \u1ea3nh...");

            const startPromise = JEELIZVTOWIDGET.start({
                placeHolder: widgetNode,
                canvas: canvasNode,
                isShadow: true,
                isRequestCamera: false,
                searchImageMask: `${jeelizBasePath}/images/target512.jpg`,
                searchImageColor: 0xeeeeee,
                callbackReady: function () {
                    isReady = true;
                    resizeWidget();
                    resizeWidget(450);
                    fitCanvasToImage(layoutImage);
                    fitCanvasToImage(layoutImage, 520);
                },
                onError: function (errorLabel) {
                    root.classList.remove("tryon-is-loading");
                    setStatus(`Kh\u00f4ng th\u1ec3 m\u1edf ch\u1ebf \u0111\u1ed9 \u1ea3nh (${errorLabel}).`);
                },
            });

            Promise.resolve(startPromise)
                .then(() => JEELIZVTO.process_image({
                    image,
                    modelSKU: activeProduct.sku,
                    nSteps: 120,
                    overSamplingFactor: 2,
                    isMask: true,
                }))
                .then(() => {
                    root.classList.remove("tryon-is-loading");
                    resizeWidget();
                    fitCanvasToImage(layoutImage);
                    fitCanvasToImage(layoutImage, 520);
                    setStatus("\u0110\u00e3 \u0111\u1eb7t k\u00ednh l\u00ean \u1ea3nh.");
                })
                .catch((errorLabel) => {
                    if (errorLabel === "FACE_NOT_FOUND" && tryonMode === "image") {
                        // Retry without mask - helps when photo already has glasses
                        root.classList.remove("tryon-no-model-active");
                        if (noModelNode) noModelNode.textContent = "";
                        setStatus("\u0110ang th\u1eed l\u1ea1i v\u1edbi ch\u1ebf \u0111\u1ed9 nh\u1eadn di\u1ec7n m\u1edf r\u1ed9ng...");
                        JEELIZVTO.process_image({
                            image,
                            modelSKU: activeProduct.sku,
                            nSteps: 120,
                            overSamplingFactor: 2,
                            isMask: false,
                        }).then(() => {
                            root.classList.remove("tryon-is-loading");
                            root.classList.remove("tryon-no-model-active");
                            if (noModelNode) noModelNode.textContent = "";
                            resizeWidget();
                            fitCanvasToImage(layoutImage);
                            fitCanvasToImage(layoutImage, 520);
                            setStatus("\u0110\u00e3 \u0111\u1eb7t k\u00ednh l\u00ean \u1ea3nh.");
                        }).catch(() => {
                            root.classList.remove("tryon-is-loading");
                            const msg = "Kh\u00f4ng nh\u1eadn ra khu\u00f4n m\u1eb7t. H\u00e3y ch\u1ecdn \u1ea3nh ch\u00ednh di\u1ec7n, m\u1eb7t \u1edf gi\u1eefa khung v\u00e0 kh\u00f4ng \u0111eo k\u00ednh.";
                            if (noModelNode) noModelNode.textContent = msg;
                            root.classList.add("tryon-no-model-active");
                            fitCanvasToImage(layoutImage);
                            fitCanvasToImage(layoutImage, 520);
                            setStatus(msg);
                        });
                        return;
                    }
                    root.classList.remove("tryon-is-loading");
                    showInlineError(`Kh\u00f4ng x\u1eed l\u00fd \u0111\u01b0\u1ee3c \u1ea3nh (${errorLabel}).`)
                });
        };

        if (isStarted || isReady) {
            stopTryon().then(run);
            return;
        }

        run();
    }

    if (startButton) {
        startButton.addEventListener("click", () => {
            if (tryonMode === "camera" && (isStarted || isReady)) {
                stopTryon().then(() => {
                    setCameraToggle(false);
                    setStatus("Camera đã tắt. Bấm để bật lại.");
                });
                return;
            }

            closeUploadPanel();
            if (uploadButton) uploadButton.classList.remove("is-active");
            setCameraToggle(true);
            loadProduct(activeProduct);
        });
    }

    if (miniCameraButton && startButton) {
        miniCameraButton.addEventListener("click", () => startButton.click());
    }

    if (uploadButton) {
        uploadButton.addEventListener("click", openUploadPanel);
    }

    if (uploadBack) {
        uploadBack.addEventListener("click", closeUploadPanel);
    }

    if (uploadCancel) {
        uploadCancel.addEventListener("click", closeUploadPanel);
    }

    if (uploadDropzone && uploadInput) {
        uploadDropzone.addEventListener("click", () => uploadInput.click());
        uploadDropzone.addEventListener("dragover", (event) => {
            event.preventDefault();
            uploadDropzone.classList.add("is-dragover");
        });
        uploadDropzone.addEventListener("dragleave", () => {
            uploadDropzone.classList.remove("is-dragover");
        });
        uploadDropzone.addEventListener("drop", (event) => {
            event.preventDefault();
            uploadDropzone.classList.remove("is-dragover");
            readImageFile(event.dataTransfer?.files?.[0]);
        });
        uploadInput.addEventListener("change", () => readImageFile(uploadInput.files?.[0]));
    }

    window.startJeelizTryon = function () {
        resizeWidget(40);
        fitCanvasToWidget(60);
        loadProduct(activeProduct);
        resizeWidget(700);
        fitCanvasToWidget(760);
    };

    window.stopJeelizTryon = function () {
        return stopTryon();
    };

    window.enterJeelizAdjustMode = function () {
        if (!window.JEELIZVTOWIDGET || typeof JEELIZVTOWIDGET.enter_adjustMode !== "function") {
            setStatus("Ch\u01b0a t\u1ea3i \u0111\u01b0\u1ee3c ch\u1ebf \u0111\u1ed9 c\u0103n ch\u1ec9nh.");
            return;
        }

        if (!isReady || tryonMode !== "camera") {
            loadProduct(activeProduct);
            window.setTimeout(() => {
                if (isReady && tryonMode === "camera") JEELIZVTOWIDGET.enter_adjustMode();
            }, 900);
            return;
        }

        JEELIZVTOWIDGET.enter_adjustMode();
    };

    function canvasToSnapshotDataUrl(canvas) {
        if (typeof canvas === "string" && canvas.startsWith("data:image/")) {
            return canvas;
        }

        if (!canvas || typeof canvas.toDataURL !== "function") {
            throw new Error("Không tìm thấy ảnh thử kính để lưu.");
        }

        return canvas.toDataURL("image/jpeg", 0.9);
    }

    function captureTryonSnapshot() {
        return new Promise((resolve, reject) => {
            let isSettled = false;

            const finish = (capturedCanvas) => {
                if (isSettled) return;
                isSettled = true;

                try {
                    resolve(canvasToSnapshotDataUrl(capturedCanvas || canvasNode));
                } catch (error) {
                    reject(error);
                }
            };

            if (window.JEELIZVTOWIDGET && typeof JEELIZVTOWIDGET.capture_image === "function" && (isStarted || isReady)) {
                try {
                    JEELIZVTOWIDGET.capture_image(1, finish, false);
                    window.setTimeout(() => finish(canvasNode), 2500);
                    return;
                } catch (error) {
                    console.error(error);
                }
            }

            finish(canvasNode);
        });
    }

    async function saveTryonSnapshot() {
        if (!activeProduct) {
            setStatus("Vui lòng chọn kính trước khi lưu kết quả.");
            return;
        }

        if (!canStoreSnapshot) {
            setStatus("Vui lòng đăng nhập để lưu kết quả thử kính.");
            window.setTimeout(() => {
                window.location.href = loginUrl;
            }, 900);
            return;
        }

        if (!snapshotStoreUrl) {
            setStatus("Chưa cấu hình đường dẫn lưu kết quả thử kính.");
            return;
        }

        if (!activeProduct.hasModel || !activeProduct.sku) {
            setStatus("Sản phẩm này chưa có model thử kính nên chưa thể lưu kết quả.");
            return;
        }

        if (!isStarted && !isReady) {
            setStatus("Hãy bật camera hoặc xử lý ảnh trước khi lưu kết quả thử kính.");
            return;
        }

        setSnapshotLoading(true);
        setStatus("Đang chụp và lưu kết quả thử kính...");

        try {
            const image = await captureTryonSnapshot();
            const response = await fetch(snapshotStoreUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
                },
                body: JSON.stringify({
                    product_id: activeProduct.id,
                    variant_id: activeProduct.variantId || null,
                    model_sku: activeProduct.sku,
                    tryon_mode: tryonMode === "image" ? "image" : "camera",
                    image,
                }),
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(payload.message || "Không lưu được kết quả thử kính.");
            }

            setStatus(payload.message || "Đã lưu kết quả thử kính.");
        } catch (error) {
            console.error(error);
            setStatus(error?.message || "Không lưu được kết quả thử kính. Vui lòng thử lại.");
        } finally {
            setSnapshotLoading(false);
        }
    }

    if (snapshotButton) {
        snapshotButton.addEventListener("click", saveTryonSnapshot);
    }

    renderProducts();
    if (activeProduct) {
        setSelectedProduct(activeProduct);
        if (!activeProduct.hasModel) {
            setNoModel(activeProduct);
        } else {
            setStatus("S\u1eb5n s\u00e0ng th\u1eed k\u00ednh.");
            if (isImmersivePage) {
                window.setTimeout(() => loadProduct(activeProduct), 350);
            }
        }
    }
})();
