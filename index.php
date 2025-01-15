<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>头像合成工具</title>
  <style>
    body {
      font-family: 'Helvetica Neue', Arial, sans-serif;
      text-align: center;
      background-color: #f0f8ff;
      color: #333;
      margin: 0;
      padding: 0;
    }
    .container {
      margin: 30px auto;
      max-width: 800px;
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      border: 1px solid #e1e1e1;
    }
    canvas {
      border: 1px solid #ccc;
      margin-top: 20px;
      border-radius: 5px;
    }
    .gradients {
      display: flex;
      overflow-x: auto;
      padding: 10px 0;
      margin-top: 10px;
      justify-content: center;
    }
    .gradients img {
      width: 50px;
      height: 50px;
      cursor: pointer;
      margin: 0 10px;
      border-radius: 5px;
      transition: transform 0.2s, border 0.2s;
      border: 2px solid transparent;
    }
    .gradients img.selected {
      border: 2px solid #007bff;
      box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
    }
    .gradients img:hover {
      transform: scale(1.1);
    }
    #downloadBtn {
      margin-top: 20px;
      padding: 10px 20px;
      background-color: #007bff;
      color: white;
      border: none;
      cursor: pointer;
      border-radius: 5px;
      transition: background-color 0.3s;
      font-size: 16px;
    }
    #downloadBtn:hover {
      background-color: #0056b3;
    }
    #imageCount {
      margin-top: 10px;
      font-size: 18px;
      color: #555;
    }
    #error-message {
      display: none;
      color: red;
      margin-top: 10px;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>头像合成工具</h1>
    
    <input type="file" id="imageUpload" accept="image/*">
    <canvas id="canvas" width="300" height="300"></canvas>
    
    <div class="gradients" id="gradientOptions"></div>
    
    <button id="downloadBtn">下载合成头像</button>
    <div id="imageCount">加载的渐变图片数量: <span id="count">0</span></div>
    <div id="error-message">发生错误，请重试。</div>
  </div>

  <script>
    let selectedGradient = '';
    let uploadedImage = null;

    const imageUpload = document.getElementById('imageUpload');
    const downloadBtn = document.getElementById('downloadBtn');
    const gradientOptions = document.getElementById('gradientOptions');
    const countElement = document.getElementById('count');
    const errorMessage = document.getElementById('error-message');

    imageUpload.addEventListener('change', handleImageUpload);
    downloadBtn.addEventListener('click', () => {
      try {
        checkCopyright();
        downloadImage();
      } catch (error) {
        console.error(error);
      }
    });

    // 获取渐变图片
    fetch('getGradients.php')
      .then(response => response.json())
      .then(data => {
        countElement.innerText = data.length;
        data.forEach((url, index) => {
          const img = document.createElement('img');
          img.src = url;
          img.dataset.src = url;
          img.alt = `渐变${index + 1}`;
          if (index === 0) img.classList.add('selected');
          img.addEventListener('click', function() {
            document.querySelectorAll('.gradients img').forEach(img => img.classList.remove('selected'));
            this.classList.add('selected');
            selectedGradient = this.dataset.src;
            if (uploadedImage) {
              updateCanvas(uploadedImage, selectedGradient);
            }
          });
          gradientOptions.appendChild(img);
        });
        if (data.length > 0) {
          selectedGradient = data[0];
        }
      })
      .catch(error => {
        console.error('加载渐变图片时发生错误:', error);
        errorMessage.innerText = '加载渐变图片时发生错误。';
        errorMessage.style.display = 'block';
      });

    // 处理图片上传
    function handleImageUpload(event) {
      const file = event.target.files[0];
      if (!file) return;

      // 文件类型验证
      const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
      if (!validTypes.includes(file.type)) {
        alert('仅支持JPEG, PNG和GIF格式的图片。');
        return;
      }

      // 文件大小限制
      const maxSize = 5 * 1024 * 1024;
      if (file.size > maxSize) {
        alert('文件大小不能超过5MB。');
        return;
      }

      const formData = new FormData();
      formData.append('image', file);

      fetch('uploadImage.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const img = new Image();
          img.onload = function () {
            uploadedImage = img;
            updateCanvas(uploadedImage, selectedGradient);
          };
          img.src = data.url;
        } else {
          alert(`上传失败: ${data.message}`);
        }
      })
      .catch(error => {
        console.error('上传发生错误:', error);
        alert('上传过程中发生错误，请稍后重试。');
      });
    }

    // 更新画布
    function updateCanvas(image, gradientSrc) {
      const canvas = document.getElementById('canvas');
      const ctx = canvas.getContext('2d');
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      ctx.drawImage(image, 0, 0, canvas.width, canvas.height);
      
      if (gradientSrc) {
        const overlayImage = new Image();
        overlayImage.crossOrigin = "anonymous";
        overlayImage.onload = function () {
          ctx.drawImage(overlayImage, 0, 0, canvas.width, canvas.height);
          addWatermark(ctx, canvas.width, canvas.height);
        };
        overlayImage.onerror = function () {
          console.error('无法加载渐变图片。');
          alert('无法加载渐变图片。');
        };
        overlayImage.src = gradientSrc;
      } else {
        addWatermark(ctx, canvas.width, canvas.height);
      }
    }

    // 添加水印
    function addWatermark(ctx, canvasWidth, canvasHeight) {
      const watermarkText = "© 2024 酷库博客";
      ctx.font = "16px Arial";
      ctx.fillStyle = "rgba(255, 255, 255, 0.7)";
      ctx.textAlign = "right";
      ctx.fillText(watermarkText, canvasWidth - 10, canvasHeight - 10);
    }

    // 下载图片
    function downloadImage() {
      if (!uploadedImage) {
        alert('请先上传头像。');
        return;
      }

      const canvas = document.getElementById('canvas');
      if (!canvas) {
        alert('无法获取画布，请稍后重试。');
        return;
      }

      try {
        const link = document.createElement('a');
        link.download = '合成头像.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
      } catch (error) {
        console.error('下载图片时发生错误:', error);
        alert('下载失败，请检查控制台日志。');
      }
    }
    
    // 版权检查
    const copyrightKey = "This is a hidden copyright key";
    document.body.dataset.copyrightKey = copyrightKey;

    function checkCopyright() {
      if (document.body.dataset.copyrightKey !== copyrightKey) {
        alert("版权信息被篡改！");
        throw new Error("请勿删除版权信息。");
      }
    }
  </script>
</body>
</html>