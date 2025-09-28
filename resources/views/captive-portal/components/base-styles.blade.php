/* Base reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-weight: 500;
}

/* Page body */
body {
    color: #ffffff;
    height: 100vh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    font-size: 1em;
    font-weight: 500;
    align-items: center;
    position: relative;
    overflow-x: hidden;
    background: linear-gradient(135deg, #eeffef 30%, #ffe0b1 100%);
}

/* Layout wrappers */
.container {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
    width: 100%;
    max-width: 400px;
    padding: 20px;
}
.content {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

/* Buttons */
.btn {
    padding: 10px 20px;
    min-width: 120px;
    font-size: 1em;
    border-radius: 5px;
    font-weight: 500 !important;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
}
.btn:hover { transform: translateY(0); }

.btn-green { background: #0e770e; color: white; }
.btn-green:hover { background: #084108; box-shadow: 0 8px 25px rgba(76, 175, 80, 0.4); }

.btn-light { background: #ffffff; color: #0e770e; }
.btn-light:hover { background: #f8f9fa; box-shadow: 0 8px 25px rgba(255, 255, 255, 0.4); }

/* Contact footer */
.contact-section {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    margin: 0;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.contact-content {
    padding: 2em 30px;
    text-align: center;
    color: #6c757d;
    line-height: 1.5;
    margin-bottom: 0px;
    font-size: 0.9em;
}
.contact-content a { color: #0e770e; text-decoration: none; transition: color 0.3s ease; }
.contact-content a:hover { color: #ff8800; text-decoration: underline; }

/* Mobile tweaks */
@media (max-width: 480px) {
    .container { padding: 15px; max-width: 380px; }
    .contact-content { padding: 12px 15px; }
}
