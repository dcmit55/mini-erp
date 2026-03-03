<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\FeatureAnnouncement;
use App\Models\Admin\FeatureAnnouncementRead;
use App\Models\Admin\User;
use App\Events\NewFeatureAnnouncement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeatureAnnouncementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display listing of announcements (Super Admin only)
     */
    public function index()
    {
        // Only super admin can manage announcements
        if (!Auth::user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $announcements = FeatureAnnouncement::latest()->withCount('reads')->paginate(15);

        return view('admin.feature-announcements.index', compact('announcements'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        if (!Auth::user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $roles = [
            'super_admin' => 'Super Admin',
            'admin_logistic' => 'Admin Logistic',
            'admin_mascot' => 'Admin Mascot',
            'admin_costume' => 'Admin Costume',
            'admin_finance' => 'Admin Finance',
            'admin_animatronic' => 'Admin Animatronic',
            'admin_procurement' => 'Admin Procurement',
            'admin_hr' => 'Admin HR',
            'admin' => 'Admin',
            'general' => 'General',
        ];

        $users = User::orderBy('username')->get();

        return view('admin.feature-announcements.create', compact('roles', 'users'));
    }

    /**
     * Store new announcement
     */
    public function store(Request $request)
    {
        if (!Auth::user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'version' => 'nullable|string|max:20',
            'priority' => 'required|in:info,important,critical',
            'target_roles' => 'nullable|array',
            'target_roles.*' => 'string',
            'target_user_ids' => 'nullable|array',
            'target_user_ids.*' => 'exists:users,id',
            'show_from' => 'nullable|date',
            'show_until' => 'nullable|date|after:show_from',
        ]);

        $announcement = FeatureAnnouncement::create($validated);

        // Get target user IDs
        $targetUserIds = $announcement->getTargetUserIds();

        // Broadcast event
        event(new NewFeatureAnnouncement($announcement, $targetUserIds));

        return redirect()
            ->route('feature-announcements.index')
            ->with('success', 'Feature announcement created and broadcasted to ' . count($targetUserIds) . ' users!');
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        if (!Auth::user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $announcement = FeatureAnnouncement::findOrFail($id);

        $roles = [
            'super_admin' => 'Super Admin',
            'admin_logistic' => 'Admin Logistic',
            'admin_mascot' => 'Admin Mascot',
            'admin_costume' => 'Admin Costume',
            'admin_finance' => 'Admin Finance',
            'admin_animatronic' => 'Admin Animatronic',
            'admin_procurement' => 'Admin Procurement',
            'admin_hr' => 'Admin HR',
            'admin' => 'Admin',
            'general' => 'General',
        ];

        $users = User::orderBy('username')->get();

        return view('admin.feature-announcements.edit', compact('announcement', 'roles', 'users'));
    }

    /**
     * Update announcement
     */
    public function update(Request $request, $id)
    {
        if (!Auth::user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $announcement = FeatureAnnouncement::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'version' => 'nullable|string|max:20',
            'priority' => 'required|in:info,important,critical',
            'target_roles' => 'nullable|array',
            'target_roles.*' => 'string',
            'target_user_ids' => 'nullable|array',
            'target_user_ids.*' => 'exists:users,id',
            'is_active' => 'boolean',
            'show_from' => 'nullable|date',
            'show_until' => 'nullable|date|after:show_from',
        ]);

        $announcement->update($validated);

        return redirect()->route('feature-announcements.index')->with('success', 'Feature announcement updated successfully!');
    }

    /**
     * Delete announcement
     */
    public function destroy($id)
    {
        if (!Auth::user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $announcement = FeatureAnnouncement::findOrFail($id);
        $announcement->delete();

        return redirect()->route('feature-announcements.index')->with('success', 'Feature announcement deleted successfully!');
    }

    /**
     * API: Get unread announcements for current user
     */
    public function getUserAnnouncements()
    {
        $user = Auth::user();

        $announcements = FeatureAnnouncement::active()
            ->currentlyShowing()
            ->unreadBy($user)
            ->latest()
            ->get()
            ->filter(function ($announcement) use ($user) {
                return $announcement->isTargetedTo($user);
            })
            ->values();

        return response()->json($announcements);
    }

    /**
     * API: Mark announcement as read
     */
    public function markAsRead($id)
    {
        $announcement = FeatureAnnouncement::findOrFail($id);

        FeatureAnnouncementRead::updateOrCreate(
            [
                'announcement_id' => $id,
                'user_id' => Auth::id(),
            ],
            [
                'read_at' => now(),
            ],
        );

        return response()->json([
            'success' => true,
            'message' => 'Announcement marked as read',
        ]);
    }

    /**
     * API: Re-broadcast announcement to targeted users
     */
    public function reBroadcast($id)
    {
        if (!Auth::user()->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $announcement = FeatureAnnouncement::findOrFail($id);
        $targetUserIds = $announcement->getTargetUserIds();

        event(new NewFeatureAnnouncement($announcement, $targetUserIds));

        return response()->json([
            'success' => true,
            'message' => 'Announcement re-broadcasted to ' . count($targetUserIds) . ' users',
        ]);
    }
}
