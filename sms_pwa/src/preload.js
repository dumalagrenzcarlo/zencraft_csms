// preload.js
const { contextBridge, ipcRenderer } = require("electron");

contextBridge.exposeInMainWorld("api", {
  saveScan: (data) => ipcRenderer.invoke("save-scan", data),
  getUnsynced: () => ipcRenderer.invoke("get-unsynced"),
  markSynced: (timestamp) => ipcRenderer.invoke("mark-synced", timestamp),
  resyncData: (days, settings) => ipcRenderer.invoke("resync-data", days, settings), // pass settings
  syncUnsynced: (settings) => ipcRenderer.invoke("sync-unsynced", settings),
  updatePeople: (settings) => ipcRenderer.invoke("update-people", settings),
  updateStudents: (settings) => ipcRenderer.invoke("update-students", settings),
  configureAutomaticUpdates: (settings) => ipcRenderer.invoke("configure-automatic-updates", settings),
  cacheSchoolLogo: (serverUrl, logoPath) => ipcRenderer.invoke("cache-school-logo", serverUrl, logoPath),
  getCachedSchoolLogo: () => ipcRenderer.invoke("get-cached-school-logo"),
  clearCachedSchoolLogo: () => ipcRenderer.invoke("clear-cached-school-logo")
});
