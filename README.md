# **📌 MedilinkSA Module for OpenEMR**
**Seamlessly integrate MedilinkSA with OpenEMR for medical aid verification and claims processing.**  

This module connects OpenEMR to the **MedilinkSA switch**, enabling healthcare providers to:  
✔ **Submit medical claims**  
✔ **Trace claim statuses**  
✔ **Reverse claims**  
✔ **Check real-time medical aid membership status**  

### **📡 Supported Features**
✅ **Real-time membership verification**  
✅ **Automatic claims submission**  
✅ **Claim tracing & reversals**  
✅ **Seamless background service integration**  

---

## **🚀 Getting Started**
### **Step 1: Obtain API Credentials**
To use this module, **register at [MedilinkSA.com](https://medilinksa.co.za)** to obtain:  
- **Client ID**
- **Username**
- **Password**
- **Production API URLs**

Once set up, you can **check membership status, submit claims, trace, reverse, and more.**  

---

## **⚙️ How It Works**
This module integrates **background services** to automate claim processing and medical aid status checks.

### **🏥 Medical Aid Status Checks**
🔹 **Manual Check:**  
- Go to the **Membership tab**  
- Enter the **Medical Aid Number**  
- Click **Check**

🔹 **Automatic Check:**  
- **During patient creation**, the module **automatically verifies membership**.

### **📨 Claims Processing**
✔ **Auto-send claims to MedilinkSA**  
✔ **Trace claim statuses**  
✔ **Reverse claims when necessary**  
✔ **Store claim responses in OpenEMR for easy tracking**  

---

## **📄 Database Changes**
This module **creates new tables** to store:
- **Claim data**
- **Response JSONs**
- **Trace logs**
- **Membership verification details**

Additionally, it registers **entries in `background_services`** to enable:  
✅ **Automatic claim submission**  
✅ **Claim tracing**  
✅ **Real-time medical aid status checks**  

---

## **📥 Installation**
### **Method 1: Manual Upload**
1. **Download the latest release** from [GitHub Releases](https://github.com/sivehost/openemr-medilinksa/releases).
2. Go to **interface/modules/custom-modules/ ** in OpenEMR directory.
3. Then **Upload Module Zip file into the OpenEMR directory** and select the ZIP file.
4. Login to the OpenEMR system, In OpenEMR go to  **Modules -> Manage Modules -> Unregistered tab** and click install on the MedilinkSA row, then go to registered and click Install and then Enable, select. It should now appear under Modules menu.

### **Method 2: Composer Installation (Packagist)**
If the module is published on **Packagist**, install via Composer:
```bash
composer require sivehost/openemr-medilinksa
```
Then enable the module in OpenEMR.

---

## **🔧 Configuration**
1. Navigate to **Admin → Globals → MedilinkSA Settings**.
2. Enter your **API credentials**.
3. Configure settings for:
   - **Automatic claims processing**
   - **Membership verification**
   - **Background claim submission**  

Once configured, the module **automates medical aid validation and claim submissions**.

---

## **🤝 Contributing**
We welcome contributions! 🚀  
- **Found a bug?** Open an issue in GitHub.  
- **Want to improve the module?** Submit a pull request.  

To contribute:  
```bash
git clone https://github.com/sivehost/openemr-medilinksa.git
cd openemr-medilinksa
git checkout -b feature-branch
```
Make your changes, commit, and submit a pull request.

---

## **📜 License**
This module is licensed under the **GNU General Public License v3.0**.  
See [LICENSE](https://github.com/sivehost/openemr-medilinksa/blob/main/LICENSE) for details.

---

## **📞 Support**
For help, visit:  
- **OpenEMR Community Forum**: [https://community.open-emr.org/](https://community.open-emr.org/)  
- **Official Documentation**: [https://github.com/openemr/openemr-modules](https://github.com/openemr/openemr-modules)  
- **Sive.Host Support**: [https://Sive.Host/](https://sive.host/)
---

### **📢 Ready to Automate Your Medical Billing?**
➡ **[Download the latest version](https://github.com/sivehost/openemr-medilinksa/releases)** and streamline your workflow today! 🚀  
