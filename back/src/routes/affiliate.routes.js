import { Router } from 'express';
import { authenticateToken, authenticateAdmin } from '../middleware/auth.js';
import { uploadSingle } from '../middleware/upload.js';
import {
  submitRequest,
  getStatus,
  listRequests,
  approveRequest,
  rejectRequest,
  deleteRequest,
  getAffiliateStats,
  trackClick,
} from '../controllers/affiliate.controller.js';

const router = Router();

router.post('/become-affiliate', uploadSingle('identityCard'), submitRequest);
router.post('/affiliate/request', authenticateToken, uploadSingle('identityCard'), submitRequest);
router.get('/affiliate/status', authenticateToken, getStatus);
router.get('/affiliate/affiliate-stats', authenticateToken, getAffiliateStats);
router.post('/track-click', trackClick);

router.get('/affiliate/requests/:tab', authenticateAdmin, listRequests);
router.post('/affiliate/:id/approve', authenticateAdmin, approveRequest);
router.post('/affiliate/:id/reject', authenticateAdmin, rejectRequest);
router.delete('/affiliate/:id', authenticateAdmin, deleteRequest);

export default router;
